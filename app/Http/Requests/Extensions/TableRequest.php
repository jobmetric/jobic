<?php

declare(strict_types=1);

namespace App\Http\Requests\Extensions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate extension table input for the authenticated panel.
 */
class TableRequest extends FormRequest
{
    /**
     * Require the same authenticated access as other panel settings.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict input to supported fields and bounded values.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'draw' => [
                'required',
                'integer',
                'min:0',
            ],
            'start' => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'length' => [
                'required',
                'integer',
                Rule::in([10, 25, 50, 100]),
            ],
            'search.value' => [
                'nullable',
                'string',
                'max:100',
            ],
            'order.0.column' => [
                'nullable',
                'integer',
                'between:1,5',
            ],
            'order.0.dir' => [
                'nullable',
                'in:asc,desc',
            ],
            'source' => ['nullable', 'in:local,composer'],
            'installation' => ['nullable', 'in:installed,available'],
            'mode' => ['nullable', 'in:single,multiple'],
            'updates' => ['nullable', 'in:available,current'],
            'structure' => ['nullable', 'in:root,owned'],
            'status' => [
                'nullable',
                'in:available,active,inactive,missing',
            ],
            'type' => [
                'nullable',
                'string', 'max:100',
            ],
        ];
    }
}
