<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate security request data.
 */
class SecurityRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'password'              => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
            'password_confirmation' => [
                'required',
                'string',
            ],
        ];
    }
}
