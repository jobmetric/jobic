<?php

declare(strict_types=1);

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate run request data.
 */
class RunRequest extends FormRequest
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
        return [];
    }
}
