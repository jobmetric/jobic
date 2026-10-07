<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxonomies;

use Illuminate\Foundation\Http\FormRequest;

/** Validate a bounded selection for the privileged bulk edit-lock release action. */
class UnlockEditLocksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'ids'   => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }
}
