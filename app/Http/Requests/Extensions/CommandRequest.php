<?php

declare(strict_types=1);

namespace App\Http\Requests\Extensions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate public extension lifecycle commands without accepting executable paths.
 */
class CommandRequest extends FormRequest
{
    /**
     * Validate extension identity, confirmation, version and optional archive.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'namespace' => ['nullable', 'string', 'max:255'],
            '_version' => ['nullable', 'string', 'size:64'],
            'confirm' => ['sometimes', 'accepted'],
            'ticket' => ['nullable', 'string', 'regex:/^[a-f0-9]{64}$/'],
            'archive' => ['nullable', 'file', 'mimes:zip', 'max:51200'],
        ];
    }
}
