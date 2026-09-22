<?php

declare(strict_types=1);

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate editable system-role fields before package-level validation.
 */
class RoleRequest extends FormRequest
{
    /**
     * Require authenticated administration access.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict role, permission, hierarchy, and rule values.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::rulesFor($this->all());
    }

    /**
     * Build operation-aware rules for HTTP and service-layer validation.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function rulesFor(array $input, array $context = []): array
    {
        $creating = ! isset($input['_version']) || $input['_version'] === '';

        return [
            '_version'        => [
                $creating ? 'prohibited' : 'required',
                'string',
                'size:64',
            ],
            'name'            => [
                'required',
                'string',
                'max:255',
            ],
            'description'     => [
                'nullable',
                'string',
                'max:2000',
            ],
            'parent_id'       => [
                'nullable',
                'integer',
                'min:1',
            ],
            'ordering'        => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'is_default'      => [
                'required',
                'boolean',
            ],
            'is_super'        => [
                'required',
                'boolean',
            ],
            'allow'           => [
                'sometimes',
                'array',
            ],
            'allow.*'         => [
                'string',
                'max:255',
                'distinct',
            ],
            'deny'            => [
                'sometimes',
                'array',
            ],
            'deny.*'          => [
                'string',
                'max:255',
                'distinct',
            ],
            'rules'           => [
                'sometimes',
                'array',
                'max:50',
            ],
            'rules.*.driver'  => [
                'required',
                'string',
                'max:255',
            ],
            'rules.*.payload' => [
                'nullable',
                'array',
            ],
        ];
    }

    /**
     * Use translated role labels in validation messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('rolix::base.fields');
    }
}
