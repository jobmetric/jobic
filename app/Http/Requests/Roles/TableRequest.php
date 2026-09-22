<?php

declare(strict_types=1);

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Authorize and bound server-side role table requests.
 */
class TableRequest extends FormRequest
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
     * Restrict paging, sorting, searching, and role filters.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'draw'           => [
                'required',
                'integer',
                'min:0',
            ],
            'start'          => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'length'         => [
                'required',
                'integer',
                Rule::in([10, 25, 50, 100]),
            ],
            'search.value'   => [
                'nullable',
                'string',
                'max:100',
            ],
            'order.0.column' => [
                'nullable',
                'integer',
                'between:1,4',
            ],
            'order.0.dir'    => [
                'nullable',
                'in:asc,desc',
            ],
            'kind'           => [
                'nullable',
                'in:default,super,regular',
            ],
        ];
    }
}
