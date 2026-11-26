<?php

declare(strict_types=1);

namespace App\Http\Requests\Layouts;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate bounded bulk layout selections.
 */
class BulkRequest extends FormRequest
{
    /**
     * Allow route middleware to own the permission decision.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the bulk-selection validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'confirm' => $this->routeIs('admin.settings.layouts.bulk.force_delete') ? ['required', 'accepted'] : ['exclude'],
            'entries' => ['required', 'array', 'min:1', 'max:100'],
            'entries.*.id' => ['required', 'integer', 'distinct'],
            'entries.*._version' => ['required', 'string', 'size:64'],
        ];
    }
}
