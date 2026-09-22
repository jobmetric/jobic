<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate website search-engine settings.
 */
class SeoRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized at the route boundary.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for public SEO metadata.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'meta_title'              => [
                'nullable',
                'string',
                'max:255',
            ],
            'meta_description'        => [
                'nullable',
                'string',
                'max:500',
            ],
            'meta_keywords'           => [
                'nullable',
                'string',
                'max:255',
            ],
            'open_graph_image'        => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'remove_open_graph_image' => [
                'required',
                'boolean',
            ],
            'robots_index'            => [
                'required',
                'boolean',
            ],
            'robots_follow'           => [
                'required',
                'boolean',
            ],
        ];
    }
}
