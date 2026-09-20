<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate profile request data.
 */
class ProfileRequest extends FormRequest
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
            'first_name'    => [
                'nullable',
                'string',
                'max:100',
            ],
            'last_name'     => [
                'nullable',
                'string',
                'max:100',
            ],
            'email'         => [
                'required',
                'email',
                'max:254',
            ],
            'avatar_preset' => [
                'nullable',
                'integer',
                Rule::in(array_merge([-1], range(1, 45))),
            ],
            'gender'        => [
                'nullable',
                'string',
                Rule::in(['male', 'female', 'other', 'prefer_not_to_say']),
            ],
        ];
    }
}
