<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate authenticator verification input without authenticating the account.
 */
class TwoFactorRequest extends FormRequest
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
            'action'           => [
                'required',
                'in:setup,enable,disable',
            ],
            'current_password' => [
                'required',
                'string',
                'current_password:web',
            ],
            'code'             => [
                'required_unless:action,setup',
                'nullable',
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
