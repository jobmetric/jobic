<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate preferences and versioned board movements before domain execution.
 */
class KanbanCommandRequest extends FormRequest
{
    /**
     * Rely on the shared route access decision.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Decode explicit collection fields sent by the shared AJAX transport.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        foreach (['card_fields','limits','items'] as $key) {
            if (is_string($this->input($key))) {
                try {
                    $value = json_decode($this->input($key), true, 32, JSON_THROW_ON_ERROR);
                    if (is_array($value)) {
                        $this->merge([$key => $value]);
                    }
                } catch (\JsonException) {
                }
            }
        }
    }

    /**
     * Describe the allowlisted board command payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['items' => 'required_if:operation,bulk|array|min:1|max:50','items.*' => 'array:post_id,definition_id,version,revision_id,state,transition,column_version,source_column_version,due_local',
            'density' => ['sometimes',Rule::in(['full','compact'])],'card_fields' => 'sometimes|array|max:5','card_fields.*' => ['string','distinct',Rule::in(['image','terms','author','updated','age'])], 'group_by' => 'sometimes|string|max:100',
            'limits' => 'present_if:operation,limits|array|max:100','limits.*' => 'array:state,limit,mode','limits.*.state' => 'required|string|max:80|distinct','limits.*.limit' => 'required|integer|min:0|max:100000','limits.*.mode' => ['required',Rule::in(['warn','enforce'])], 'stale_days' => 'required_if:operation,limits|integer|min:1|max:3650','options_version' => 'required_if:operation,limits|integer|min:0',
            'board_version' => 'sometimes|nullable|integer|min:1',
            'operation' => ['required', Rule::in(['settings', 'limits', 'move', 'reorder','bulk'])],
            'page_size' => ['required_if:operation,settings', 'integer', Rule::in([5, 10, 20, 50])],
            'post_id' => 'required_unless:operation,settings,limits,bulk|integer|min:1',
            'definition_id' => 'required_unless:operation,settings,limits,bulk|integer|min:1',
            'version' => 'required_unless:operation,settings,limits,bulk|integer|min:0',
            'revision_id' => 'required_unless:operation,settings,limits,bulk|integer|min:1',
            'column_version' => 'required_unless:operation,settings,limits,bulk|integer|min:0',
            'source_column_version' => 'required_if:operation,move|integer|min:0',
            'state' => 'required_unless:operation,settings,limits,bulk|string|max:80',
            'transition' => 'required_if:operation,move|string|max:80',
            'before_id' => 'sometimes|nullable|integer|min:1',
            'due_local' => 'sometimes|nullable|string|max:40',
            'due_at' => 'sometimes|nullable|date|after:now',
        ];
    }
}
