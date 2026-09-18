<?php

declare(strict_types=1);

namespace App\Http\Requests\Languages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JobMetric\Language\Enums\CalendarTypeEnum;

/**
 * Validate language management input for the authenticated panel.
 */
class LanguageRequest extends FormRequest
{
    /**
     * Require the same authenticated access as other panel settings.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict input to supported fields and bounded values.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            '_version'          => [
                'sometimes',
                'string',
                'size:64',
            ],
            'name'              => [
                'required',
                'string',
                'max:100',
            ],
            'locale'            => [
                'required',
                'regex:/^[a-z]{2}$/',
            ],
            'direction'         => [
                'required',
                'in:ltr,rtl',
            ],
            'calendar'          => [
                'required',
                Rule::in(CalendarTypeEnum::values()),
            ],
            'first_day_of_week' => [
                'required',
                'integer',
                'between:0,6',
            ],
            'status'            => [
                'required',
                'boolean',
            ],
            'flag'              => [
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    /**
     * Use the translated field labels in validation messages.
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return array_intersect_key(trans('languages'), $this->rules());
    }
}
