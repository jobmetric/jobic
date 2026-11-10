<?php

declare(strict_types=1);
namespace App\Http\Requests\Posts;
use Illuminate\Foundation\Http\FormRequest;

/** Validate bounded cursor reads of the content board. */
class KanbanReadRequest extends FormRequest
{
    /** Delegate authentication and permission checks to the route boundary.
     *
     * @return bool
     */
    public function authorize(): bool { return true; }

    /** Define the public read query contract.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['state' => 'sometimes|string|max:80', 'cursor' => 'sometimes|nullable|string|max:3000',
            'locale' => ['sometimes', \Illuminate\Validation\Rule::in(\App\Support\LocaleCatalog::codes())], 'definition_id' => 'sometimes|integer|min:1'];
    }
}
