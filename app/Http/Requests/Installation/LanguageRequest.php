<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use App\Support\LocaleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        return [
            'locale' => [
                'required',
                'string',
                Rule::in(LocaleCatalog::codes()),
            ],
        ];
    }
}
