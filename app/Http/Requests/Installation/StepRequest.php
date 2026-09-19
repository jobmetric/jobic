<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use App\Support\Installation\Fields;
use Illuminate\Foundation\Http\FormRequest;

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
        return [
                'step' => [
                    'required',
                    'integer',
                    'in:1,2,5',
                ],
            ] + Fields::rules((int) ($input['step'] ?? 0) === 1 ? [] : Fields::fields((int) ($input['step'] ?? 0)));
    }
}
