<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

/** Validate commands for the post-type workflow designer. */
class WorkflowRequest extends FormRequest
{
    /**
     * Leave authorization to the registered route permission.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate draft and activation envelopes before domain graph validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'operation'         => [
                'required',
                'in:draft,preview,activate,simulate',
            ],
            'graph'             => [
                'required_if:operation,draft,simulate',
                'array',
            ],
            'scenario'          => [
                'required_if:operation,simulate',
                'array',
            ],
            'scenario.state_id' => [
                'required_if:operation,simulate',
                'string',
                'max:80',
            ],
            'scenario.locale'   => [
                'required_if:operation,simulate',
                'string',
                'max:20',
            ],
            'scenario.role_id'  => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
            ],
            'scenario.title'    => [
                'sometimes',
                'nullable',
                'string',
                'max:300',
            ],
            'scenario.excerpt'  => [
                'sometimes',
                'nullable',
                'string',
                'max:3000',
            ],
            'scenario.body'     => [
                'sometimes',
                'nullable',
                'string',
                'max:20000',
            ],
            'scenario.approved' => [
                'sometimes',
                'boolean',
            ],
            'enabled'           => [
                'required_if:operation,draft',
                'boolean',
            ],
            'version'           => [
                'required_if:operation,draft',
                'integer',
                'min:0',
            ],
            'definition_id'     => [
                'required_if:operation,preview,activate',
                'integer',
                'min:1',
            ],
            'fingerprint'       => [
                'required_if:operation,activate',
                'string',
                'size:64',
            ],
            'mapping'           => [
                'sometimes',
                'array',
            ],
            'mapping.*'         => [
                'string',
                'max:80',
            ],
            'confirmed'         => [
                'required_if:operation,activate',
                'in:1,true,on,yes',
            ],
        ];
    }
}
