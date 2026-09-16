<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Facades\Authentication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Handle authenticated session page requests.
 */
class AuthenticatedSessionController extends Controller
{
    /**
     * Render the authentication form.
     *
     * @return View
     */
    public function create(): View
    {
        Page::prepare('auth', 'fa');

        return view('auth.login');
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
        Authentication::logout([
            'session' => $request->session(),
        ]);

        return redirect()->route('login');
    }
}
