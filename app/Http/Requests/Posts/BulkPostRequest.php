<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate an atomic bulk operation on posts of one registered type.
 */
final class BulkPostRequest extends FormRequest
{
    /**
     * Determine whether the route-level post permission may handle the request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the validation rules for selected post identifiers and operation.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'operation' => ['required', 'string', 'in:activate,deactivate,delete,unlock'],
            'ids'       => ['required', 'array', 'min:1', 'max:100'],
            'ids.*'     => ['required', 'integer', 'distinct', 'min:1'],
        ];
    }
}
