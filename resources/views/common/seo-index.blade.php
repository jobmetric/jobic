<section class="crud-capability-card seo-index-card" @if($disabled ?? false) inert @endif>
    <header><h2>{{ __('taxonomies.seo_settings') }}</h2></header>
    <fieldset class="crud-form-fields" @disabled($disabled ?? false)>
        <x-form.field variant="crud" :label="__('taxonomies.seo_index')" for="seo-index" error="metadata.seo_index">
            <x-form.select id="seo-index" name="metadata[seo_index]">
                <option value="1" @selected((string) $value === '1')>{{ __('taxonomies.yes') }}</option>
                <option value="0" @selected((string) $value === '0')>{{ __('taxonomies.no') }}</option>
            </x-form.select>
        </x-form.field>
    </fieldset>
</section>
