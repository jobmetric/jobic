<?php

declare(strict_types=1);

namespace App\Http\Requests\Menus;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Authorize a menu deletion submitted from the administration panel.
 */
class DeleteMenuRequest extends FormRequest
{
    /**
     * Require an authenticated panel session.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * A menu deletion has no request payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
