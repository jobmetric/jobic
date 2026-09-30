<?php

declare(strict_types=1);

namespace App\Http\Requests\Menus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate menu container input for authenticated administrators.
 */
class MenuRequest extends FormRequest
{
    /**
     * Require an authenticated panel session.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict menu input to stable taxonomy fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '_version' => ['sometimes', 'string', 'size:64'],
            'name' => ['required', 'string', 'max:150'],
            'status' => ['required', 'boolean'],
            'ordering' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    /**
     * Use localized field labels in validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('menus.name'),
            'status' => __('menus.status'),
            'ordering' => __('menus.ordering'),
        ];
    }
}
