@php
    $fieldValue = old('translation.'.$locale.'.'.$field, $translation[$field] ?? '');
    $fieldError = 'translation.'.$locale.'.'.$field;
    $fieldId = 'post-'.$field.'-'.$locale;
    $fieldName = 'translation['.$locale.']['.$field.']';
    $fieldLabel = $field === 'title' ? __('posts.title_field') : __('posts.fields.'.$field);
    $fieldRequired = $field === 'title' && $locale === $activeLocale;
@endphp
@if($field === 'content')
    @cmsHook('jobic.editor.render.before', ['type' => $type, 'locale' => $locale, 'post_id' => $record?->id])
    @php
        $fieldId = 'post-editor-'.$locale;
    @endphp
    <section class="editor-workbench" data-editor-workbench>
        <header class="editor-workbench__header">
            <div class="editor-workbench__title"><span class="editor-workbench__title-icon"><x-vuero.icon name="edit" size="24" /></span><strong id="{{ $fieldId }}-label" data-editor-heading>{{ __('posts.content') }}</strong></div>
            <div class="editor-workbench__actions">
                <button type="button" class="vuero-button" data-editor-preview hidden><x-vuero.icon name="eye" size="18" /><span>{{ __('editor.preview') }}</span></button>
                <button type="button" class="vuero-button" data-editor-templates><x-vuero.icon name="layout" size="18" /><span>{{ __('editor.templates') }}</span></button>
                <button type="button" class="vuero-button" data-editor-fullscreen><x-vuero.icon name="maximize" size="18" /><span>{{ __('editor.fullscreen') }}</span></button>
                <button type="button" class="vuero-button" data-editor-sidebar-toggle hidden>{{ __('editor.sidebar') }}</button>
            </div>
        </header>
        <div class="editor-workbench__layout">
            <div class="jobic-editor" id="{{ $fieldId }}" aria-labelledby="{{ $fieldId }}-label" data-editor data-locale="{{ $locale }}" data-content="{{ e($fieldValue) }}"></div>
            <aside class="editor-workbench__sidebar" data-editor-sidebar hidden>
                <div class="editor-workbench__tabs" role="tablist">
                    <button type="button" class="is-active" role="tab" aria-selected="true" data-editor-tab="blocks">{{ __('editor.blocks_tab') }}</button>
                    <button type="button" role="tab" aria-selected="false" data-editor-tab="settings">{{ __('editor.settings_tab') }}</button>
                </div>
                <div data-editor-panel="blocks"><input type="search" class="input" data-editor-search placeholder="{{ __('editor.search') }}" aria-label="{{ __('editor.search') }}"><div data-editor-blocks></div></div>
                <div data-editor-panel="settings" hidden><div class="crud-hint" data-editor-settings>{{ __('editor.select_block') }}</div></div>
            </aside>
        </div>
        <input type="hidden" name="{{ $fieldName }}" data-editor-content="{{ $locale }}" value="{{ $fieldValue }}">
        <span data-error-for="{{ $fieldError }}" class="crud-field-error"></span>
    </section>
    @cmsHook('jobic.editor.render.after', ['type' => $type, 'locale' => $locale, 'post_id' => $record?->id])
@elseif($field === 'excerpt' || $field === 'meta_description')
    <x-form.field variant="crud" :label="$fieldLabel" :label-hint="$field === 'meta_description' ? __('posts.seo_hints.meta_description') : null" :for="$fieldId" :error="$fieldError"><x-form.textarea :id="$fieldId" :name="$fieldName" :value="$fieldValue" :maxlength="$field === 'meta_description' ? 500 : 10000" /><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
@else
    <x-form.field variant="crud" :label="$fieldLabel" :label-hint="in_array($field, ['meta_title', 'meta_keywords'], true) ? __('posts.seo_hints.'.$field) : null" :for="$fieldId" :error="$fieldError"><x-form.input :id="$fieldId" :name="$fieldName" :value="$fieldValue" :maxlength="in_array($field, ['title', 'meta_title', 'meta_keywords'], true) ? ($field === 'meta_keywords' ? 1000 : 255) : 10000" :required="$fieldRequired" /><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
@endif
