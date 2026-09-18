<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate general request data.
 */
class GeneralRequest extends FormRequest
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
            'APP_NAME' => [
                'required',
                'string',
                'max:100',
                'not_regex:/[\r\n\x00]/',
            ],
            'APP_URL'  => [
                'required',
                'url:http,https',
                'max:2048',
            ],
        ];
    }
}
