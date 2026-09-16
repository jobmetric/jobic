<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
        return ['locale' => ['required', 'string', 'in:en,fa']];
    }
}
