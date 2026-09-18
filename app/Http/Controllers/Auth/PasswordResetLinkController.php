<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Facades\Authentication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Support\LocaleCatalog;
use App\Support\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Handle password reset link page requests.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Render the authentication form.
     *
     * @return View
     */
    public function create(): View
    {
        Page::prepare('auth', LocaleCatalog::resolve(session('auth_locale', 'fa')));
        Page::addLocalize('auth', ['show_password' => trans('auth.screen.show'), 'hide_password' => trans('auth.screen.hide')]);

        return view('auth.forgot-password');
    }

    /**
     * Process the submitted authentication data.
     *
     * @param ForgotPasswordRequest $request
     *
     * @return RedirectResponse
     * @throws Throwable
     */
    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $result = Authentication::sendResetLink($request->validated(), [
            'ip' => $request->ip(),
        ]);

        if (! $result->ok) {
            throw ValidationException::withMessages([
                'email' => $result->message,
            ]);
        }

        return back()->with('status', $result->message);
    }
}
