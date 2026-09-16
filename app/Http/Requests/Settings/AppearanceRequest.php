<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate appearance request data.
 */
class AppearanceRequest extends FormRequest
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
        return ['layout' => ['required', 'string', Rule::in(config('admin.layouts'))], 'theme' => ['required', 'in:system,light,dark'], 'motion' => ['required', 'in:system,full,reduced'], 'locale' => ['required', 'in:en,fa']];
    }
}
