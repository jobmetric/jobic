<?php

declare(strict_types=1);

namespace App\Http\Requests\Extensions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate explicitly selected plugin instances and their observed versions.
 */
class BulkRequest extends FormRequest
{
    /**
     * Bound the selection and require confirmation for every bulk mutation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operation' => ['required', 'in:activate,deactivate,delete'],
            'entries' => ['required', 'array', 'min:1', 'max:100'],
            'entries.*.id' => ['required', 'integer', 'min:1', 'distinct'],
            'entries.*._version' => ['required', 'string', 'size:64'],
            'confirm' => ['required', 'accepted'],
        ];
    }
}
