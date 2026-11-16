<?php

declare(strict_types=1);

namespace App\Http\Requests\Extensions;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate an opaque review identifier without accepting filesystem paths.
 */
class DiscardArchiveRequest extends FormRequest
{
    /**
     * Require the actor's review ticket for immediate staging cleanup.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['ticket' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/']];
    }
}
