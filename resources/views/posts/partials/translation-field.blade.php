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
    <x-form.field variant="crud" :label="__('posts.content')" :for="$fieldId" :error="$fieldError"><div class="jobic-editor" id="{{ $fieldId }}" data-editor data-locale="{{ $locale }}" data-content="{{ e($fieldValue) }}"></div><input type="hidden" name="{{ $fieldName }}" data-editor-content="{{ $locale }}" value="{{ $fieldValue }}"><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
    @cmsHook('jobic.editor.render.after', ['type' => $type, 'locale' => $locale, 'post_id' => $record?->id])
@elseif($field === 'excerpt' || $field === 'meta_description')
    <x-form.field variant="crud" :label="$fieldLabel" :label-hint="$field === 'meta_description' ? __('posts.seo_hints.meta_description') : null" :for="$fieldId" :error="$fieldError"><x-form.textarea :id="$fieldId" :name="$fieldName" :value="$fieldValue" :maxlength="$field === 'meta_description' ? 500 : 10000" /><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
@else
    <x-form.field variant="crud" :label="$fieldLabel" :label-hint="in_array($field, ['meta_title', 'meta_keywords'], true) ? __('posts.seo_hints.'.$field) : null" :for="$fieldId" :error="$fieldError"><x-form.input :id="$fieldId" :name="$fieldName" :value="$fieldValue" :maxlength="in_array($field, ['title', 'meta_title', 'meta_keywords'], true) ? ($field === 'meta_keywords' ? 1000 : 255) : 10000" :required="$fieldRequired" /><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
@endif
