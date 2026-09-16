<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate language request data.
 */
class LanguageRequest extends FormRequest
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
        return ['locale' => ['required', 'string', 'in:en,fa']];
    }
}
