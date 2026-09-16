<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Facades\Authentication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Support\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handle new password page requests.
 */
class NewPasswordController extends Controller
{
    /**
     * Render the authentication form.
     *
     * @param Request $request
     * @param string $token
     *
     * @return View
     */
    public function create(Request $request, string $token): View
    {
        Page::prepare('auth', 'fa');

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->string('email')->value(),
        ]);
    }

    /**
     * Process the submitted authentication data.
     *
     * @param ResetPasswordRequest $request
     *
     * @return RedirectResponse
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $result = Authentication::resetPassword($request->validated());

        if (! $result->ok) {
            throw ValidationException::withMessages([
                'email' => $result->message,
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', $result->message);
    }
}
