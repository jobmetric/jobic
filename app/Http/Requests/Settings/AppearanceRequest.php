<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AppearanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['layout' => ['required', 'string', Rule::in(config('admin.layouts'))], 'theme' => ['required', 'in:system,light,dark'], 'motion' => ['required', 'in:system,full,reduced'], 'locale' => ['required', 'in:en,fa']];
    }
}
