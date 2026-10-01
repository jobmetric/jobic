<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxonomies;

use App\Support\LocaleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
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
        $locales = LocaleCatalog::codes();
        $activeLocale = LocaleCatalog::resolve(app()->getLocale());
        $rules = [
            'translation' => ['required', 'array:'.implode(',', $locales)],
            'ordering' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', 'boolean'],
        ];

        foreach ($locales as $locale) {
            $rules["translation.$locale"] = [$locale === $activeLocale ? 'required' : 'sometimes', 'array'];
            foreach ($builder->getTranslation() as $translation) {
                $field = (string) Arr::get($translation->customField->params, 'uniqName');
                if ($field === '') {
                    continue;
                }
                $validation = $translation->customField->validation ?: 'nullable|string';
                $fieldRules = is_array($validation) ? $validation : explode('|', $validation);
                $fieldRules = array_values(array_filter($fieldRules, fn ($rule) => $rule !== 'required'));
                if ($locale === $activeLocale && $field === 'name') {
                    array_unshift($fieldRules, 'required');
                } else {
                    array_unshift($fieldRules, 'nullable');
                }
                $rules["translation.$locale.$field"] = array_unique($fieldRules);
            }
        }

        if ($builder->hasHierarchical()) {
            $rules['parent_id'] = ['nullable', 'integer', Rule::exists(config('taxonomy.tables.taxonomy'), 'id')->where('type', $type)];
        }
        if ($builder->hasUrl()) {
            $rules['slug'] = ['nullable', 'string', 'max:255'];
        }
        if ($builder->hasBaseMedia()) {
            $rules['media_upload.base'] = ['nullable', 'image'];
        }
        foreach ($builder->getMedia() as $media) {
            $collection = $media->getCollection();
            $rules["media_upload.$collection"] = $media->getMultiple()
                ? ['nullable', 'array'] : ['nullable', 'file'];
            if ($media->getMultiple()) {
                $rules["media_upload.$collection.*"] = ['file'];
            }
        }

        return $rules;
    }
}
