<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxonomies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validate safe bulk operations shared by taxonomy types. */
class BulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['ids' => ['required', 'array', 'min:1', 'max:100'], 'ids.*' => ['integer', 'distinct'], 'operation' => ['required', Rule::in(['activate', 'deactivate', 'delete'])]];
    }
}
