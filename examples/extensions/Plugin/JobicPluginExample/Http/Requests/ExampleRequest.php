<?php

declare(strict_types=1);

namespace App\Extensions\Plugin\JobicPluginExample\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Admit authenticated readers after the route's registered Rolix permission check.
 */
class ExampleRequest extends FormRequest
{
    /**
     * Require an authenticated CMS user.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Accept no user input for this read-only preview.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
