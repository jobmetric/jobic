<?php

declare(strict_types=1);

namespace App\Http\Requests\Taxonomies;

use App\Facades\Hooks;
use App\Support\LocaleCatalog;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/** Validate the native Jobic taxonomy form against its registered type. */
class TaxonomyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Build a validation rule set for the taxonomy type and its registered fields.
     *
     * @return array<string, mixed>
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function rules(): array
    {
        $type = (string) $this->route('type');

        $builder = TaxonomyTypeRegistry::for($type);
        $locales = LocaleCatalog::codes();
        $activeLocale = LocaleCatalog::resolve(app()->getLocale());

        $rules = [
            '_version'    => ['sometimes', 'string', 'size:64'],
            'translation' => [
                'required',
                'array:' . implode(',', $locales),
            ],
            'ordering'    => [
                'required',
                'integer',
                'min:0',
                'max:1000000',
            ],
            'status'      => [
                'required',
                'boolean',
            ],
        ];

        if ($this->route('taxonomy') && TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false)) {
            $rules['_version'] = ['required', 'string', 'size:64'];
            $rules['_edit_lock'] = ['required', 'string', 'uuid'];
        }

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

        foreach (($builder->get()['custom-fields'] ?? []) as $key => $field) {
            $rules["custom_fields.$key"] = $field['rules'];

            if ($field['type'] === 'select') {
                $fieldRules = is_array($rules["custom_fields.$key"]) ? $rules["custom_fields.$key"] : explode('|', $rules["custom_fields.$key"]);
                $rules["custom_fields.$key"] = [...$fieldRules, Rule::in(array_keys($field['options']))];
            }
        }
        if (($builder->get()['seo'] ?? false)) {
            $rules['seo'] = ['sometimes', 'array:' . implode(',', $locales)];

            foreach ($locales as $locale) {
                $rules["seo.$locale.title"] = [
                    'nullable',
                    'string',
                    'max:255',
                ];
                $rules["seo.$locale.description"] = [
                    'nullable',
                    'string',
                    'max:500',
                ];
                $rules["seo.$locale.index"] = [
                    'sometimes',
                    'boolean',
                ];
            }
        }

        if ($builder->hasHierarchical()) {
            $rules['parent_id'] = [
                'nullable',
                'integer',
                Rule::exists(config('taxonomy.tables.taxonomy'), 'id')
                    ->where('type', $type),
            ];
        }

        if ($builder->hasUrl()) {
            $rules['slug'] = [
                'nullable',
                'string',
                'max:255',
            ];
        }

        if ($builder->hasBaseMedia()) {
            $rules['media_selection.base'] = [
                'nullable',
                'json',
            ];
        }

        if (($builder->get()['seo'] ?? false)) {
            $rules['media_selection.seo_image'] = [
                'nullable',
                'json',
            ];
        }

        foreach ($builder->getMedia() as $media) {
            $collection = $media->getCollection();

            $rules["media_selection.$collection"] = [
                'nullable',
                'json',
            ];
        }

        return Hooks::filter('jobic.taxonomies.validation.rules', $rules, [
            'type'    => $type,
            'request' => $this,
            'builder' => $builder,
        ]);
    }
}
