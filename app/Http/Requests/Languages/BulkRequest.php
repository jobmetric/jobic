<?php

declare(strict_types=1);

namespace App\Http\Requests\Languages;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate language management input for the authenticated panel.
 */
class BulkRequest extends FormRequest
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
