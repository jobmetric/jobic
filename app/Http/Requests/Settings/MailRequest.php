<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Support\Installation\Fields;
use Illuminate\Foundation\Http\FormRequest;

class MailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Fields::rules(Fields::mail());
    }
}
