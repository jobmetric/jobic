<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\LoginEvent;
use App\Facades\Hooks;
use App\Http\Requests\Auth\LanguageRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use JobMetric\PackageCore\Output\Response;
use Throwable;

/**
 * Authenticate users and manage password recovery.
 */
class Authentication
{
    /**
     * Validate and persist the guest interface language in the current session.
     *
     * @param array<string, mixed> $data
     *
     * @return Response
     * @throws Throwable
     */
    public function language(array $data): Response
    {
        $data = dto($data, LanguageRequest::class);
        $context = ['locale' => $data['locale']];
        Hooks::action('jobic.auth.language.saving', $context);
        session()->put('auth_locale', $data['locale']);
        app()->setLocale($data['locale']);
        Hooks::notify('jobic.auth.language.saved', $context);

        return Response::make(true, trans('auth.screen.language_saved'));
    }

    private const int LOGIN_MAX_ATTEMPTS = 5;

    private const int FORGOT_MAX_ATTEMPTS = 5;

    /**
     * Login user with email and password.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function login(array $data, array $context = []): Response
    {
        $validated = dto($data, LoginRequest::class);
        $email = Str::lower($validated['email']);
        $ip = (string) ($context['ip'] ?? '');
        $userAgent = (string) ($context['user_agent'] ?? '');
        $remember = (bool) ($validated['remember'] ?? false);

        $throttleKey = $this->loginThrottleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($throttleKey, self::LOGIN_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return Response::make(false, trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]), null, 429);
        }

        Auth::guard('web')->setRememberDuration(max(1, (int) config('auth.remember_days', 30)) * 24 * 60);

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $remember)) {
            RateLimiter::hit($throttleKey);

            $this->recordLoginEvent($email, null, $ip, $userAgent, false);

            return Response::make(false, trans('auth.failed'), null, 422);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        $user = Auth::user();

        request()->session()->regenerate();

        $this->recordLoginEvent($email, $user->id, $ip, $userAgent, true);

        return Response::make(true, trans('auth.login_success'), $user);
    }

    /**
     * Logout user.
     *
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function logout(array $context = []): Response
    {
        Auth::guard('web')->logout();

        if (isset($context['session'])) {
            $context['session']->invalidate();
            $context['session']->regenerateToken();
        }

        return Response::make(true, trans('auth.logout_success'));
    }

    /**
     * Send password reset link to user email.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function sendResetLink(array $data, array $context = []): Response
    {
        $validated = dto($data, ForgotPasswordRequest::class);
        $email = Str::lower($validated['email']);
        $ip = (string) ($context['ip'] ?? '');

        $throttleKey = $this->forgotThrottleKey($email, $ip);

        if (RateLimiter::tooManyAttempts($throttleKey, self::FORGOT_MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return Response::make(false, trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => (int) ceil($seconds / 60),
                ]), null, 429,);
        }

        RateLimiter::hit($throttleKey);

        Password::sendResetLink(['email' => $validated['email']]);

        return Response::make(true, trans('auth.reset_link_sent'));
    }

    /**
     * Reset user password.
     *
     * @param array<string, mixed> $data
     *
     * @return Response
     * @throws Throwable
     */
    public function resetPassword(array $data): Response
    {
        $validated = dto($data, ResetPasswordRequest::class);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill([
                'password' => $password,
            ])->save();
        },);

        if ($status !== Password::PASSWORD_RESET) {
            return Response::make(false, trans($status), null, 422);
        }

        return Response::make(true, trans('auth.password_reset_success'));
    }

    /**
     * Generate a unique key for login throttling based on email and IP address.
     *
     * @param string $email The user's email address.
     * @param string $ip The user's IP address.
     *
     * @return string A unique key for throttling login attempts.
     */
    private function loginThrottleKey(string $email, string $ip): string
    {
        return Str::transliterate($email . '|' . $ip);
    }

    /**
     * Generate a unique key for forgot password throttling based on email and IP address.
     *
     * @param string $email The user's email address.
     * @param string $ip The user's IP address.
     *
     * @return string A unique key for throttling forgot password attempts.
     */
    private function forgotThrottleKey(string $email, string $ip): string
    {
        return Str::transliterate('forgot|' . $email . '|' . $ip);
    }

    /**
     * Record a login event in the database.
     *
     * @param string $email The user's email address.
     * @param int|null $userId The user's ID, or null if not available.
     * @param string $ip The user's IP address.
     * @param string $userAgent The user's user agent string.
     * @param bool $successful Whether the login attempt was successful.
     *
     * @return void
     */
    private function recordLoginEvent(
        string $email,
        ?int $userId,
        string $ip,
        string $userAgent,
        bool $successful,
    ): void {
        LoginEvent::query()->create([
            'email'      => $email,
            'user_id'    => $userId,
            'ip_address' => $ip !== '' ? $ip : null,
            'user_agent' => $userAgent !== '' ? Str::limit($userAgent, 500) : null,
            'successful' => $successful,
            'created_at' => now(),
        ]);
    }
}
