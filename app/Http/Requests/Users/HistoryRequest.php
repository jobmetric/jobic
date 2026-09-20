<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorize and bound user history pagination.
 */
class HistoryRequest extends FormRequest
{
    /**
     * Require authenticated administration access.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Restrict history reads to a bounded positive page number.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100000',
            ],
        ];
    }
}
