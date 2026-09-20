<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Facades\Authentication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LanguageRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Services\Auth\Authentication as AuthenticationService;
use App\Support\LocaleCatalog;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use Throwable;

/**
 * Handle authenticated session page requests.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Render the authentication form.
     *
     * @throws SetPluginNotFoundException
     */
    public function create(): View
    {
        app()->setLocale(LocaleCatalog::resolve(session('auth_locale', 'fa')));
        Page::prepare('auth');
        Page::addLocalize('auth', [
            'show_password' => trans('auth.screen.show'),
            'hide_password' => trans('auth.screen.hide'),
        ]);

        return view('auth.login');
    }

    /**
     * Save the guest language and return the standard settings response.
     *
     * @param LanguageRequest $request
     *
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function language(LanguageRequest $request): JsonResponse|RedirectResponse
    {
        $result = Authentication::language($request->validated());

        return $request->expectsJson() ? response()->json($result, $result->status) : redirect()->route('login');
    }

    /**
     * Process the submitted authentication data.
     *
     * @param LoginRequest $request
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $result = Authentication::login($request->validated(), [
            'ip'         => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        if (! $result->ok) {
            throw ValidationException::withMessages([
                'email' => $result->message,
            ]);
        }

        if (is_array($result->data) && ($result->data['two_factor'] ?? false)) {
            return redirect()->route('login.two-factor');
        }

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Render the challenge for an unexpired password-verified guest session.
     *
     * @throws Throwable
     */
    public function twoFactor(): View|RedirectResponse
    {
        if (session('auth.two_factor.expires', 0) < time()) {
            return redirect()->route('login');
        }

        app()->setLocale(LocaleCatalog::resolve(session('auth_locale', 'fa')));

        Page::prepare('auth');
        Page::addLocalize('auth', [
            'show_password' => __('auth.screen.show'),
            'hide_password' => __('auth.screen.hide'),
        ]);

        return view('auth.two-factor');
    }

    /**
     * Finish login after validating the second factor.
     *
     * @param TwoFactorChallengeRequest $request
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function challenge(TwoFactorChallengeRequest $request): RedirectResponse
    {
        $result = app(AuthenticationService::class)->challenge($request->validated(), [
            'ip'         => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        if (! $result->ok) {
            throw ValidationException::withMessages(['code' => $result->message]);
        }

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    /**
     * Log out the authenticated user.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function destroy(Request $request): RedirectResponse
    {
        $intendedUrl = $this->resolveLogoutIntendedUrl($request);

        Authentication::logout([
            'session' => $request->session(),
        ]);

        if ($intendedUrl !== null) {
            $request->session()->put('url.intended', $intendedUrl);
        }

        return redirect()->route('login');
    }

    /**
     * Resolve the internal page that initiated the logout request.
     *
     * @param Request $request
     *
     * @return string|null
     */
    private function resolveLogoutIntendedUrl(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $refererHost = parse_url($referer, PHP_URL_HOST);
        $refererScheme = parse_url($referer, PHP_URL_SCHEME);
        if ($refererHost !== $request->getHost() || $refererScheme !== $request->getScheme()) {
            return null;
        }

        $path = parse_url($referer, PHP_URL_PATH);
        if (! is_string($path) || $path === '/login' || $path === '/logout') {
            return null;
        }

        return $referer;
    }
}
