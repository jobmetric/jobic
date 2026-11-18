<?php

declare(strict_types=1);

namespace App\Http\Requests\Extensions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate explicitly selected extension identities and their observed lifecycle versions.
 */
class ExtensionBulkRequest extends FormRequest
{
    /**
     * Require a bounded, distinct and explicitly confirmed extension selection.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1', 'max:100'],
            'entries.*.namespace' => ['required', 'string', 'max:255', 'distinct'],
            'entries.*._version' => ['required', 'string', 'size:64'],
            'confirm' => ['required', 'accepted'],
        ];
    }
}
