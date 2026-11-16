<?php

declare(strict_types=1);

namespace App\Http\Requests\Extensions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate which explicitly requested example archive may be downloaded.
 */
class ExampleRequest extends FormRequest
{
    /**
     * Bind validation to the route identifier rather than a query-string override.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['variant' => $this->route('variant')]);
    }

    /**
     * Allow only the bundled, removable test extensions.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['variant' => ['required', Rule::in(array_keys(config('extensions.examples', [])))], 'version' => ['nullable', 'in:1.0.0,1.1.0']];
    }
}
