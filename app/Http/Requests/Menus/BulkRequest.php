<?php

declare(strict_types=1);

namespace App\Http\Requests\Menus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate atomic operations on explicitly selected menus.
 */
class BulkRequest extends FormRequest
{
    /**
     * Require an authenticated panel session.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict selection size and supported operations.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'operation' => ['required', 'in:delete,activate,deactivate'],
        ];
    }
}
