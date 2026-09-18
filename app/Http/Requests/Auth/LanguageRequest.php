<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Support\LocaleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate the guest sign-in language preference.
 */
class LanguageRequest extends FormRequest
{
    /**
     * Allow guests to select their own interface language.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Limit the preference to supported interface languages.
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
