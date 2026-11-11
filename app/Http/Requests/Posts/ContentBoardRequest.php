<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JobMetric\Rolix\Models\Role;

/** Validate named board definitions before domain normalization. */
class ContentBoardRequest extends FormRequest
{
    /**
     * Leave capability and permission enforcement at the route boundary.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Decode the explicit JSON collection fields used by AJAX transport.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        foreach (['roles', 'users', 'states', 'filters'] as $key) {
            $value = $this->input($key);
            if (is_string($value)) {
                try {
                    $value = json_decode($value, true, 32, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    continue;
                }
                if (is_array($value)) {
                    $this->merge([$key => $value]);
                }
            }
        }
    }

    /**
     * Describe bounded settings and optimistic concurrency input.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['name' => 'required|string|max:160', 'version' => 'required|integer|min:0',
            'roles' => 'present|array|max:100', 'roles.*' => ['integer', 'distinct', Rule::exists((new Role)->getTable(), 'id')->where('type', 'system')],
            'users' => 'present|array|max:100', 'users.*' => ['integer', 'distinct', Rule::exists((new User)->getTable(), 'id')],
            'states' => 'required|array|min:1|max:100', 'states.*' => 'required|string|max:80|distinct',
            'filters' => 'present|array|max:20', 'filters.*' => 'array:driver,config',
            'filters.*.driver' => 'required|string|max:80', 'filters.*.config' => 'required|array'];
    }
}
