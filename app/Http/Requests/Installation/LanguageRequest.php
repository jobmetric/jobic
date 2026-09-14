<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

class LanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['locale' => ['required', 'string', 'in:en,fa']];
    }
}
