@foreach($builder->getTranslation() as $translationField)
    @php
        $field = $translationField->customField;
        $key = (string) data_get($field->params, 'uniqName');
        $name = "translation[$locale][$key]";
        $id = "taxonomy-$locale-$key";
        $isSeoField = in_array($key, ['meta_title', 'meta_description', 'meta_keywords'], true);
        $savedValue = data_get($translations, "$locale.$key");
        if (blank($savedValue) && $key === 'meta_title') $savedValue = $capabilityValues['seo'][$locale]['title'] ?? '';
        if (blank($savedValue) && $key === 'meta_description') $savedValue = $capabilityValues['seo'][$locale]['description'] ?? '';
        $value = old("translation.$locale.$key", $savedValue);
        $required = $locale === $activeLocale && $key === 'name';
        $label = $isSeoField ? __('taxonomies.'.$key) : trans($field->label ?: $key);
        if ($label === $field->label) $label = trans('taxonomies.'.$key);
    @endphp
    @continue(isset($section) && ($section === 'seo') !== $isSeoField)
    @if($key !== '')
        <x-form.field variant="crud" :label="$label" :label-hint="$isSeoField ? __('taxonomies.seo_hints.'.$key) : null" :for="$id" :error="'translation.'.$locale.'.'.$key">
            @if(in_array($field->type, ['textarea', 'editor'], true) || $key === 'meta_description')
                <x-form.textarea :id="$id" :name="$name" :value="$value" :maxlength="$key === 'meta_description' ? 500 : null" :required="$required" />
            @else
                <x-form.input :id="$id" :name="$name" :value="$value" :maxlength="$key === 'meta_keywords' ? 1000 : 255" :required="$required" />
            @endif
        </x-form.field>
    @endif
@endforeach
