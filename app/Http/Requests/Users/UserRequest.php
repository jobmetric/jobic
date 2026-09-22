<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validate user creation and editing fields without accepting private account state.
 */
class UserRequest extends FormRequest
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
     * Restrict editable identity, password and status values.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::rulesFor($this->all());
    }

    /**
     * Build operation-aware rules for both HTTP and service-layer validation.
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
            '_version' => [
                $creating ? 'prohibited' : 'required',
                'string',
                'size:64',
            ],
            'name' => [
                'required',
                'string',
                'max:100',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                $creating ? 'required' : 'nullable',
                'string',
                Password::min(12),
                'confirmed',
            ],
            'password_confirmation' => [
                $creating ? 'required' : 'nullable',
                'string',
            ],
            'status' => [
                'required',
                'boolean',
            ],
            'role_ids' => [
                'sometimes',
                'array',
                'max:50',
            ],
            'role_ids.*' => [
                'integer',
                'distinct',
                Rule::exists(config('rolix.tables.role'), 'id')->where('type', 'system'),
            ],
        ];
    }

    /**
     * Use translated user labels in validation messages.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return array_intersect_key(trans('users'), self::rulesFor($this->all()));
    }
}
