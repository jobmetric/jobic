<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate atomic user selection operations.
 */
class BulkRequest extends FormRequest
{
    /**
     * Require authenticated administration access.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict operations to bounded distinct user identifiers.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ids'       => [
                'required',
                'array',
                'min:1',
                'max:100',
            ],
            'ids.*'     => [
                'required',
                'integer',
                'min:1',
                'distinct',
            ],
            'operation' => [
                'required',
                'in:delete,activate,deactivate',
            ],
        ];
    }
}
