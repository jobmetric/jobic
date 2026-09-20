<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate authenticator verification input without authenticating the account.
 */
class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * Allow route middleware to enforce the authentication boundary.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the allowed authenticator operation and credential format.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'regex:/^(?:[0-9]{6}|[a-f0-9]{16})$/',
            ],
        ];
    }

    /**
     * Supply translated field labels for validation failures.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'code'             => __('two-factor.code'),
            'current_password' => __('two-factor.password'),
        ];
    }
}
