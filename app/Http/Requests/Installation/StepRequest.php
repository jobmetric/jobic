<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use App\Support\Installation\Fields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return self::rulesFor($this->all());
    }

    public static function rulesFor(array $input, array $context = []): array
    {
        return ['step' => ['required', 'integer', 'between:1,5']] + Fields::rules(Fields::fields((int) ($input['step'] ?? 0)));
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (($validator->getData()['SESSION_SAME_SITE'] ?? '') === 'none' && ! filter_var($validator->getData()['SESSION_SECURE_COOKIE'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
                $validator->errors()->add('SESSION_SECURE_COOKIE', trans('installation.secure_required'));
            }
        });
    }
}
