<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

/** Validate explicit versioned board selections for atomic deletion. */
class ContentBoardBulkRequest extends FormRequest
{
    /**
     * Delegate authorization to the board-management route boundary.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Decode the single allowlisted selection field from shared AJAX transport.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('items'))) {
            try {
                $this->merge(['items' => json_decode($this->input('items'), true, 16, JSON_THROW_ON_ERROR)]);
            } catch (\JsonException) {
            }
        }
    }

    /**
     * Bound and validate every selected identifier and optimistic version.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['items' => 'required|array|min:1|max:100', 'items.*' => 'array:id,version',
            'items.*.id' => 'required|integer|min:1|distinct', 'items.*.version' => 'required|integer|min:1'];
    }
}
