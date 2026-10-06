@if(($builder->get()['custom-fields'] ?? []))
<section class="crud-capability-card">
    <header><h2>{{ __('taxonomies.custom_fields') }}</h2></header>
    <fieldset class="crud-form-fields">
    @foreach(($builder->get()['custom-fields'] ?? []) as $key => $field)
        @php($value = old('custom_fields.'.$key, $capabilityValues['custom_fields'][$key] ?? ($field['default'] ?? null)))
        <x-form.field variant="crud" :label="__($field['label'])" :for="'taxonomy-field-'.$key" :error="'custom_fields.'.$key">
        @if($field['type'] === 'textarea')
            <x-form.textarea :id="'taxonomy-field-'.$key" :name="'custom_fields['.$key.']'" :value="$value" />
        @elseif($field['type'] === 'select' || $field['type'] === 'checkbox')
            <x-form.select :id="'taxonomy-field-'.$key" :name="'custom_fields['.$key.']'">
            @foreach($field['type'] === 'checkbox' ? [0 => __('taxonomies.no'), 1 => __('taxonomies.yes')] : $field['options'] as $option => $label)
                <option value="{{ $option }}" @selected((string)$value === (string)$option)>{{ __($label) }}</option>
            @endforeach
            </x-form.select>
        @else
            <x-form.input :id="'taxonomy-field-'.$key" :name="'custom_fields['.$key.']'" :type="$field['type']" :value="$value" />
        @endif
        </x-form.field>
    @endforeach
    </fieldset>
</section>
@endif
@if(($builder->get()['seo'] ?? false))
<section class="crud-capability-card">
    <header><h2>{{ __('taxonomies.seo') }}</h2></header>
    @foreach($languages as $seoLocale => $language)
    <details class="crud-help-section" @if($seoLocale === $activeLocale) open @endif>
        <summary>{{ $language['name'] }}</summary>
        <fieldset class="crud-form-fields">
            @foreach(['title', 'description'] as $key)
            <x-form.field variant="crud" :label="__('taxonomies.seo_'.$key)" :for="'seo-'.$seoLocale.'-'.$key" :error="'seo.'.$seoLocale.'.'.$key">
                <x-form.input :id="'seo-'.$seoLocale.'-'.$key" :name="'seo['.$seoLocale.']['.$key.']'" :value="old('seo.'.$seoLocale.'.'.$key, $capabilityValues['seo'][$seoLocale][$key] ?? '')" :maxlength="$key === 'title' ? 255 : 500" />
            </x-form.field>
            @endforeach
            <x-form.field variant="crud" :label="__('taxonomies.seo_index')" :for="'seo-'.$seoLocale.'-index'" :error="'seo.'.$seoLocale.'.index'">
                <x-form.select :id="'seo-'.$seoLocale.'-index'" :name="'seo['.$seoLocale.'][index]'">
                    <option value="1" @selected(($capabilityValues['seo'][$seoLocale]['index'] ?? 1) == 1)>{{ __('taxonomies.yes') }}</option>
                    <option value="0" @selected(($capabilityValues['seo'][$seoLocale]['index'] ?? 1) == 0)>{{ __('taxonomies.no') }}</option>
                </x-form.select>
            </x-form.field>
        </fieldset>
    </details>
    @endforeach
</section>
@endif
