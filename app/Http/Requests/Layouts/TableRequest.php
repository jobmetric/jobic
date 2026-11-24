<?php

declare(strict_types=1);

namespace App\Http\Requests\Layouts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate layout table filters and pagination input.
 */
class TableRequest extends FormRequest
{
    /**
     * Allow the route permission middleware to own access control.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return bounded server-side table rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'draw' => ['required', 'integer', 'min:0'],
            'start' => ['required', 'integer', 'min:0', 'max:1000000'],
            'length' => ['required', 'integer', Rule::in([10, 25, 50, 100])],
            'search.value' => ['nullable', 'string', 'max:100'],
            'order.0.column' => ['nullable', 'integer', 'between:1,5'],
            'order.0.dir' => ['nullable', Rule::in(['asc', 'desc'])],
            'theme' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'deleted'])],
        ];
    }
}
