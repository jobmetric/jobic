<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Events\Auth\AuthLanguageEvent;
use App\Facades\Hooks;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LanguageRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Models\LoginEvent;
use App\Models\User;
use App\Support\LocaleCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use JobMetric\EventSystem\Support\CommittedEventDispatcher;
use JobMetric\PackageCore\Output\Response;
use Throwable;

/**
 * Authenticate users and manage password recovery.
 */
class Authentication
{
    private const int LOGIN_MAX_ATTEMPTS = 5;

    private const int FORGOT_MAX_ATTEMPTS = 5;

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

        Hooks::lifecycle('jobic.auth.language.saving', $context);

        $outcomeLocale = session('auth_locale');
        session()->put('auth_locale', $data['locale']);
        app()->setLocale($data['locale']);

        if ($outcomeLocale !== $data['locale']) {
            app(CommittedEventDispatcher::class)->dispatch(new AuthLanguageEvent($data['locale']));
        }

        Hooks::notify('jobic.auth.language.saved', $context);

        return Response::make(true, trans('auth.screen.language_saved'));
    }

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

        Auth::guard('web')
            ->setRememberDuration(max(1, (int) config('auth.remember_days', 30)) * 24 * 60);

        session()->forget('auth.two_factor');
        $credentials = [
            'email'    => $validated['email'],
            'password' => $validated['password'],
        ];
        $provider = Auth::guard('web')
            ->getProvider();
        $user = $provider->retrieveByCredentials($credentials);
        if (! $user || ! $provider->validateCredentials($user, $credentials)) {
            RateLimiter::hit($throttleKey);

            $this->recordLoginEvent($email, null, $ip, $userAgent, false);

            return Response::make(false, trans('auth.failed'), null, 422);
        }

        if (! $user->status) {
            RateLimiter::hit($throttleKey);
            $this->recordLoginEvent($email, $user->id, $ip, $userAgent, false);

            return Response::make(false, trans('auth.failed'), null, 422);
        }

        RateLimiter::clear($throttleKey);

        $provider->rehashPasswordIfRequired($user, $credentials);
        if ($user->two_factor_enabled_at) {
            session()->regenerate();
            session()->put('auth.two_factor', [
                'user_id'       => $user->id,
                'expires'       => time() + 300,
                'password_hash' => hash('sha256', $user->password),
                'factor_hash'   => hash('sha256', $user->two_factor_secret),
            ]);

            return Response::make(true, __('two-factor.challenge_title'), ['two_factor' => true]);
        }
        Auth::guard('web')
            ->login($user, $remember);

        request()
            ->session()
            ->regenerate();
        $this->synchronizeAuthenticatedLocale($user);

        $this->recordLoginEvent($email, $user->id, $ip, $userAgent, true);

        if (true) {
            app(CommittedEventDispatcher::class)->dispatch(new \App\Events\Auth\AuthLoginEvent((int) $user->id), $user->getConnectionName());
        }

        return Response::make(true, trans('auth.login_success'), $user);
    }

    /**
     * Complete a password-verified session using a single-use second factor.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function challenge(array $data, array $context = []): Response
    {
        $values = dto($data, TwoFactorChallengeRequest::class);

        $pending = session('auth.two_factor');
        if (! is_array($pending) || ($pending['expires'] ?? 0) < time()) {
            session()->forget('auth.two_factor');

            return Response::make(false, __('two-factor.expired'), null, 422);
        }

        $key = 'two-factor:' . $pending['user_id'];
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return Response::make(false, __('auth.throttle', [
                'seconds' => RateLimiter::availableIn($key),
                'minutes' => 1,
            ]), null, 429);
        }

        RateLimiter::hit($key, 300);

        $user = DB::transaction(function () use ($pending, $values): ?User {
            $user = User::query()
                ->lockForUpdate()
                ->find($pending['user_id']);
            if (! $user || ! $user->status || ! hash_equals($pending['password_hash'], hash('sha256', $user->password)) || ! hash_equals($pending['factor_hash'], hash('sha256', $user->two_factor_secret ?? '')) || ! app(TwoFactor::class)->consume($user, $values['code'])) {
                return null;
            }

            return $user;
        });

        if (! $user) {
            return Response::make(false, __('two-factor.invalid'), null, 422);
        }

        RateLimiter::clear($key);
        session()->forget('auth.two_factor');

        // Persistent remember cookies would bypass the required second factor on a later login.
        Auth::guard('web')
            ->login($user, false);

        session()->regenerate();
        $this->synchronizeAuthenticatedLocale($user);

        $this->recordLoginEvent($user->email, $user->id, (string) ($context['ip'] ?? ''), (string) ($context['user_agent'] ?? ''), true);

        if (true) {
            app(CommittedEventDispatcher::class)->dispatch(new \App\Events\Auth\AuthLoginEvent((int) $user->id), $user->getConnectionName());
        }

        return Response::make(true, __('auth.login_success'));
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
        $outcomeUserId = Auth::guard('web')
            ->id();
        Auth::guard('web')
            ->logout();

        if (isset($context['session'])) {
            $context['session']->invalidate();
            $context['session']->regenerateToken();
        }

        if ($outcomeUserId !== null) {
            app(CommittedEventDispatcher::class)->dispatch(new \App\Events\Auth\AuthLogoutEvent((int) $outcomeUserId));
        }

        return Response::make(true, trans('auth.logout_success'));
    }

    /**
     * Carry the successful sign-in language into the account preferences.
     *
     * @param User $user
     *
     * @return void
     */
    private function synchronizeAuthenticatedLocale(User $user): void
    {
        $locale = LocaleCatalog::resolve(session('auth_locale', app()->getLocale()));
        $preferences = array_replace(config('admin.appearance'), $user->ui_preferences ?? [], ['locale' => $locale]);

        $user->forceFill(['ui_preferences' => $preferences])
            ->save();
        app()->setLocale($locale);
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
            ]), null, 429);
        }

        RateLimiter::hit($throttleKey);

        $outcomeResetStatus = Password::sendResetLink(['email' => $validated['email']]);
        if ($outcomeResetStatus === Password::RESET_LINK_SENT) {
            $outcomeUser = User::query()
                ->where('email', $validated['email'])
                ->first();
            if ($outcomeUser) {
                app(CommittedEventDispatcher::class)->dispatch(new \App\Events\Auth\AuthResetLinkEvent((int) $outcomeUser->id), $outcomeUser->getConnectionName());
            }
        }

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
            ])
                ->save();
            if ($user->wasChanged('password')) {
                app(CommittedEventDispatcher::class)->dispatch(new \App\Events\Auth\AuthPasswordResetEvent((int) $user->id), $user->getConnectionName());
            }

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
     * @param string $ip    The user's IP address.
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
     * @param string $ip    The user's IP address.
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
     * @param string $email     The user's email address.
     * @param int|null $userId  The user's ID, or null if not available.
     * @param string $ip        The user's IP address.
     * @param string $userAgent The user's user agent string.
     * @param bool $successful  Whether the login attempt was successful.
     *
     * @return void
     */
    private function recordLoginEvent(
        string $email,
        ?int $userId,
        string $ip,
        string $userAgent,
        bool $successful
    ): void {
        LoginEvent::query()
            ->create([
                'email'      => $email,
                'user_id'    => $userId,
                'ip_address' => $ip !== '' ? $ip : null,
                'user_agent' => $userAgent !== '' ? Str::limit($userAgent, 500) : null,
                'successful' => $successful,
                'created_at' => now(),
            ]);
    }
}
