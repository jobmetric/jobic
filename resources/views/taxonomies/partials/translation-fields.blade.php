@foreach($builder->getTranslation() as $translationField)
    @php
        $field = $translationField->customField;
        $key = (string) data_get($field->params, 'uniqName');
        $name = "translation[$locale][$key]";
        $id = "taxonomy-$locale-$key";
        $value = old("translation.$locale.$key", data_get($translations, "$locale.$key"));
        $required = $locale === $activeLocale && $key === 'name';
        $label = trans($field->label ?: $key);
    @endphp
    @if($key !== '')
        <x-form.field variant="crud" :label="$label" :for="$id" :error="'translation.'.$locale.'.'.$key">
            @if(in_array($field->type, ['textarea', 'editor'], true))
                <x-form.textarea :id="$id" :name="$name" :value="$value" :required="$required" />
            @else
                <x-form.input :id="$id" :name="$name" :value="$value" maxlength="255" :required="$required" />
            @endif
        </x-form.field>
    @endif
@endforeach
