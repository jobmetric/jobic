<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validate reset password request data.
 */
class ResetPasswordRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token'                 => [
                'required',
                'string',
            ],
            'email'                 => [
                'required',
                'string',
                'email',
            ],
            'password'              => [
                'required',
                'string',
                'confirmed',
                Password::min(8),
            ],
            'password_confirmation' => [
                'required',
                'string',
            ],
        ];
    }
}
