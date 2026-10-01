<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxonomies;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorize and bound taxonomy history pagination.
 */
class HistoryRequest extends FormRequest
{
    /**
     * Require authenticated administration access.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict history reads to a positive bounded page.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1', 'max:100000']];
    }
}
