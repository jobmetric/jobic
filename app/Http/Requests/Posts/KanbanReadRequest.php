<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate bounded cursor reads of the content board.
 */
class KanbanReadRequest extends FormRequest
{
    /**
     * Delegate authentication and permission checks to the route boundary.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Decode read-only driver configuration transported in the query string.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('filters'))) {
            try {
                $value = json_decode($this->input('filters'), true, 32, JSON_THROW_ON_ERROR);
                if (is_array($value)) {
                    $this->merge(['filters' => $value]);
                }
            } catch (\JsonException) {
            }
        }
    }

    /**
     * Define the public read query contract.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['search' => 'sometimes|nullable|string|max:160','filters' => 'sometimes|array|max:20','filters.*' => 'array:driver,config','filters.*.driver' => 'required|string|max:80','filters.*.config' => 'required|array','summary' => 'sometimes|nullable|in:scheduled,stale','group_by' => 'sometimes|string|max:100','lane' => 'sometimes|nullable|string|max:100','lane_offset' => 'sometimes|integer|min:0|max:100000','board_version' => 'sometimes|integer|min:1','state' => 'sometimes|string|max:80', 'cursor' => 'sometimes|nullable|string|max:3000',
            'locale' => ['sometimes', \Illuminate\Validation\Rule::in(\App\Support\LocaleCatalog::codes())], 'definition_id' => 'sometimes|integer|min:1'];
    }
}
