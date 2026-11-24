<?php

declare(strict_types=1);

namespace App\Http\Requests\Layouts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate a named Theme layout and its ordered Module placements.
 */
class LayoutRequest extends FormRequest
{
    /**
     * Normalize an empty editor into an explicit placement list.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['plugins' => is_array($this->input('plugins')) ? $this->input('plugins') : []]);
    }

    /**
     * Allow authorization middleware to own the access decision.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return layout validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'status' => 'required|boolean',
            'theme' => 'required|string|max:100',
            'page_types' => 'required|array|min:1',
            'page_types.*' => 'required|string|distinct|max:100',
            'plugins' => 'present|array',
            'plugins.*.plugin_id' => 'required|integer|exists:plugins,id',
            'plugins.*.position' => 'required|string|max:100',
            'plugins.*.ordering' => 'required|integer|min:0',
            '_version' => 'nullable|string|max:100',
        ];
    }
}
