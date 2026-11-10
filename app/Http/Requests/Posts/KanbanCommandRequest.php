<?php

declare(strict_types=1);
namespace App\Http\Requests\Posts;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validate preferences and versioned board movements before domain execution. */
class KanbanCommandRequest extends FormRequest
{
    /** Rely on the shared route access decision.
     *
     * @return bool
     */
    public function authorize(): bool { return true; }

    /** Describe the allowlisted board command payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operation' => ['required', Rule::in(['settings', 'move', 'reorder'])],
            'page_size' => ['required_if:operation,settings', 'integer', Rule::in([5, 10, 20, 50])],
            'post_id' => 'required_unless:operation,settings|integer|min:1',
            'definition_id' => 'required_unless:operation,settings|integer|min:1',
            'version' => 'required_unless:operation,settings|integer|min:0',
            'revision_id' => 'required_unless:operation,settings|integer|min:1',
            'column_version' => 'required_unless:operation,settings|integer|min:0',
            'source_column_version' => 'required_if:operation,move|integer|min:0',
            'state' => 'required_unless:operation,settings|string|max:80',
            'transition' => 'required_if:operation,move|string|max:80',
            'before_id' => 'sometimes|nullable|integer|min:1',
            'due_local' => 'sometimes|nullable|string|max:40',
            'due_at' => 'sometimes|nullable|date|after:now',
        ];
    }
}
