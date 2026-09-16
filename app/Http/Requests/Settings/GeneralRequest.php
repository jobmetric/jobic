<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class GeneralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['APP_NAME' => ['required', 'string', 'max:100', 'not_regex:/[\r\n\x00]/'], 'APP_URL' => ['required', 'url:http,https', 'max:2048']];
    }
}
