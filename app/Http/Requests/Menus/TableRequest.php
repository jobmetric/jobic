<?php

declare(strict_types=1);

namespace App\Http\Requests\Menus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate bounded menu table queries from authenticated administrators.
 */
class TableRequest extends FormRequest
{
    /**
     * Require an authenticated panel session.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict paging, sorting, searching, and filtering values.
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
            'order.0.column' => ['nullable', 'integer', 'between:1,4'],
            'order.0.dir' => ['nullable', 'in:asc,desc'],
            'status' => ['nullable', 'in:0,1'],
        ];
    }
}
