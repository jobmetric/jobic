<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use App\Support\Installation\Fields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validate step request data.
 */
class StepRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::rulesFor($this->all());
    }

    /**
     * Get the validation rules for the selected installation step.
     *
     * @param array<array-key, mixed> $input
     * @param array<string, mixed> $context
     *
     * @return array<array-key, mixed>
     */
    public static function rulesFor(array $input, array $context = []): array
    {
        return ['step' => ['required', 'integer', 'between:1,5']] + Fields::rules(Fields::fields((int) ($input['step'] ?? 0)));
    }

    /**
     * Register additional validation checks.
     *
     * @param Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (($validator->getData()['SESSION_SAME_SITE'] ?? '') === 'none' && ! filter_var($validator->getData()['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $validator->errors()->add('SESSION_SECURE_COOKIE', trans('installation.secure_required'));
            }
        });
    }
}
