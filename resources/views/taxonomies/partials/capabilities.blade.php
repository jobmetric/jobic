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
