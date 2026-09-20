<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate an immediate avatar selection request.
 */
class AvatarRequest extends FormRequest
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
     * Get the avatar selection validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'avatar_preset' => [
                'required',
                'integer',
                Rule::in(array_merge([-1], range(1, 45))),
            ],
        ];
    }
}
