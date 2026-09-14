<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use App\Support\Installation\Fields;
use Illuminate\Foundation\Http\FormRequest;

class RedisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return Fields::rules(Fields::groups(3)['redis']);
    }
}
