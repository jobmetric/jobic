<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use App\Support\Installation\Fields;
use Illuminate\Foundation\Http\FormRequest;

class DatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Fields::rules(Fields::fields(2));
    }
}
