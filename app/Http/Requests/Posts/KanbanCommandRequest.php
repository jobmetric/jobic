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
        foreach (['card_fields','limits','items','dependency_ids','reviewer_ids','notification_preferences'] as $key) {
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
            'density' => ['sometimes',Rule::in(['full','compact'])],'card_fields' => 'sometimes|array|max:9','card_fields.*' => ['string','distinct',Rule::in(['image','terms','author','updated','age','assignee','scheduled','deadline','dependencies'])], 'group_by' => 'sometimes|string|max:100',
            'limits' => 'present_if:operation,limits|array|max:100','limits.*' => 'array:state,limit,mode','limits.*.state' => 'required|string|max:80|distinct','limits.*.limit' => 'required|integer|min:0|max:100000','limits.*.mode' => ['required',Rule::in(['warn','enforce'])], 'stale_days' => 'required_if:operation,limits|integer|min:1|max:3650','color_mode' => ['sometimes',Rule::in(['none','signals','priority'])], 'options_version' => 'required_if:operation,limits|integer|min:0',
            'board_version' => 'sometimes|nullable|integer|min:1',
            'operation' => ['required', Rule::in(['settings', 'limits', 'move', 'reorder','bulk','card_work','comment','notification_read','notification_preferences','request_review','review_decide'])],
            'page_size' => ['required_if:operation,settings', 'integer', Rule::in([5, 10, 20, 50])],
            'post_id' => 'required_unless:operation,settings,limits,bulk,notification_read,notification_preferences|integer|min:1',
            'comment_body' => 'required_if:operation,comment|string|min:1|max:2000',
            'notification_id' => 'required_if:operation,notification_read|integer|min:1',
            'notification_preferences' => 'required_if:operation,notification_preferences|array:mention,assignment,deadline,review_requested,review_decided',
            'notification_preferences.*' => 'boolean',
            'reviewer_ids' => 'required_if:operation,request_review|array|min:1|max:5',
            'reviewer_ids.*' => 'integer|min:1|distinct',
            'decision' => ['required_if:operation,review_decide',Rule::in(['approve','reject'])],
            'definition_id' => 'required_unless:operation,settings,limits,bulk,card_work,comment,notification_read,notification_preferences,request_review,review_decide|integer|min:1',
            'version' => 'required_unless:operation,settings,limits,bulk,card_work,comment,notification_read,notification_preferences|integer|min:0',
            'revision_id' => 'required_unless:operation,settings,limits,bulk,card_work,comment,notification_read,notification_preferences|integer|min:1',
            'column_version' => 'required_unless:operation,settings,limits,bulk,card_work,comment,notification_read,notification_preferences,request_review,review_decide|integer|min:0',
            'source_column_version' => 'required_if:operation,move|integer|min:0',
            'state' => 'required_unless:operation,settings,limits,bulk,card_work,comment,notification_read,notification_preferences,request_review,review_decide|string|max:80',
            'meta_version' => 'required_if:operation,card_work|integer|min:0',
            'priority' => ['sometimes', Rule::in(['low','normal','high','urgent'])],
            'assignee_id' => 'sometimes|nullable|integer|min:1',
            'work_due_local' => 'sometimes|nullable|string|max:40',
            'dependency_ids' => 'sometimes|array|max:20',
            'dependency_ids.*' => 'integer|min:1|distinct',
            'transition' => 'required_if:operation,move|string|max:80',
            'before_id' => 'sometimes|nullable|integer|min:1',
            'due_local' => 'sometimes|nullable|string|max:40',
            'due_at' => 'sometimes|nullable|date',
        ];
    }
}
