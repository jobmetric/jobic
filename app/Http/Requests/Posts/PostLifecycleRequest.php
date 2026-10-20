<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validate an action against the post-wide publication stream and revision. */
final class PostLifecycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operation'   => [
                'required',
                Rule::in([
                    'transition',
                    'submit',
                    'approve',
                    'reject',
                    'publish',
                    'schedule',
                    'resume',
                    'cancel_schedule',
                    'withdraw',
                ]),
            ],
            'locale'      => [
                'required',
                Rule::in(['*']),
            ],
            'revision_id' => [
                'required_unless:operation,cancel_schedule,withdraw',
                'integer',
                'min:1',
            ],
            'version'     => [
                'required',
                'integer',
                'min:0',
            ],
            'transition'  => [
                'required_if:operation,transition',
                'string',
                'max:80',
            ],
            'due_at'      => [
                'required_if:operation,schedule,resume',
                'date',
                'after:now',
            ],
            'schedule_id' => [
                'required_if:operation,resume,cancel_schedule',
                'integer',
                'min:1',
            ],
            '_edit_lock'  => [
                'sometimes',
                'string',
                'uuid',
            ],
        ];
    }
}
