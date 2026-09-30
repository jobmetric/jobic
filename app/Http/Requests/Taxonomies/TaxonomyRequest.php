<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxonomies;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;

/** Validate the native Jobic taxonomy form against its registered type. */
class TaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $type = (string) $this->route('type');
        $builder = TaxonomyTypeRegistry::for($type);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'ordering' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', 'boolean'],
        ];

        if ($builder->hasHierarchical()) {
            $rules['parent_id'] = ['nullable', 'integer', Rule::exists(config('taxonomy.tables.taxonomy'), 'id')->where('type', $type)];
        }
        if ($builder->hasUrl()) {
            $rules['slug'] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }
}
