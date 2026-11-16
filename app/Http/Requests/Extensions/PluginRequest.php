<?php

declare(strict_types=1);

namespace App\Http\Requests\Extensions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate the CMS envelope before package form validation.
 */
class PluginRequest extends FormRequest
{
    /**
     * Validate editable plugin fields and optimistic version.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'fields' => ['sometimes', 'array'],
            '_version' => ['nullable', 'string', 'size:64'],
        ];
    }
}
