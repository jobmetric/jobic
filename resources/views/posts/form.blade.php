@extends('common.admin')
@section('title', __('posts.'.($record ? 'edit' : 'create')))
@section('content')
@push('styles') @vite('resources/css/posts.css', 'vuero/build') @endpush
<section class="crud-page post-editor-page">
<form class="crud-form" data-post-form data-type="{{ $type }}" action="{{ $record ? route('admin.posts.update', ['type' => $type, 'post' => $record->id]) : route('admin.posts.store', ['type' => $type]) }}" method="post">
    @csrf @if($record) @method('PUT') @endif
    <header class="crud-toolbar"><div><h1>{{ __('posts.'.($record ? 'edit' : 'create')) }} · {{ __($typeLabel) }}</h1><p>{{ __('posts.form_intro') }}</p></div><div class="crud-form-actions"><a class="vuero-button" href="{{ route('admin.posts.index', ['type' => $type]) }}">{{ __('posts.cancel') }}</a><button type="submit" class="vuero-button is-primary">{{ __('posts.save') }}</button></div></header>
    @cmsHook('jobic.admin.posts.form.before', ['type' => $type, 'post_id' => $record?->id])
    <x-form.field variant="crud" :label="__('posts.status')" for="post-status" error="status"><x-form.select id="post-status" name="status">@foreach(['draft','pending','publish','future','private','archive','disabled'] as $status)<option value="{{ $status }}" @selected(old('status', $record?->status ?? 'draft') === $status)>{{ __('posts.statuses.'.$status) }}</option>@endforeach</x-form.select><span data-error-for="status" class="crud-field-error"></span></x-form.field>
    <x-form.field variant="crud" :label="__('posts.published_at')" for="post-published-at" error="published_at"><x-form.input id="post-published-at" type="datetime-local" name="published_at" :value="old('published_at', $record?->published_at?->format('Y-m-d\TH:i'))" /><span data-error-for="published_at" class="crud-field-error"></span></x-form.field>
    <x-form.field variant="crud" :label="__('posts.url_slug')" for="post-slug" error="slug"><x-form.input id="post-slug" name="slug" :value="old('slug', $record?->slug)" maxlength="100" dir="ltr" required /><span data-error-for="slug" class="crud-field-error"></span></x-form.field>
    @if($customFields !== [])
        <section class="post-custom-fields"><h2>{{ __('posts.custom_fields') }}</h2>
        @foreach($customFields as $key => $definition)
            @php
                $metaId = 'post-meta-'.$key;
                $metaName = 'metadata['.$key.']';
                $metaLabel = __($definition['label'] ?? $key);
                $metaError = 'metadata.'.$key;
                $metaType = ($definition['type'] ?? 'text') === 'number' ? 'number' : 'text';
                $metaValue = old('metadata.'.$key, $metadata[$key] ?? '');
            @endphp
            <x-form.field variant="crud" :label="$metaLabel" :for="$metaId" :error="$metaError"><x-form.input :id="$metaId" :name="$metaName" :type="$metaType" :value="$metaValue" /><span data-error-for="metadata.{{ $key }}" class="crud-field-error"></span></x-form.field>
        @endforeach
        </section>
    @endif
    @foreach($taxonomyOptions as $collection => $options)
        @php
            $taxonomyId = 'post-taxonomy-'.$collection;
            $taxonomyName = 'taxonomies['.$collection.'][]';
            $taxonomyLabel = $taxonomyLabels[$collection] ?? trans('posts.'.\Illuminate\Support\Str::singular($collection));
            $taxonomyError = 'taxonomies.'.$collection;
            $taxonomyMultiple = (bool) ($taxonomyTypes[$collection]['multiple'] ?? true);
        @endphp
        <x-form.field variant="crud" :label="$taxonomyLabel" :for="$taxonomyId" :error="$taxonomyError"><x-form.select :id="$taxonomyId" :name="$taxonomyName" :multiple="$taxonomyMultiple">@foreach($options as $option)<option value="{{ $option['id'] }}" @selected(in_array($option['id'], old('taxonomies.'.$collection, $selectedTaxonomies[$collection] ?? [])))>{{ $option['name'] }}</option>@endforeach</x-form.select></x-form.field>
    @endforeach
    @foreach($mediaCollections as $collection => $definition)
        @php
            $mediaGroups = collect($definition['mimeTypes'] ?? ['image', 'document', 'audio', 'video'])
                ->map(fn ($mimeType) => \Illuminate\Support\Str::before($mimeType, '/'))
                ->unique()
                ->values()
                ->all();
        @endphp
        <section class="post-media-field" data-media-picker data-multiple="{{ ($definition['multiple'] ?? false) ? 'true' : 'false' }}" data-groups="{{ e(json_encode($mediaGroups)) }}">
            <h2>{{ __('posts.media.'.$collection) }}</h2>
            <div class="taxonomy-media-picker__selected" data-media-selected>
                @foreach($selectedMedia[$collection] ?? [] as $item)
                    <article data-media-id="{{ $item['id'] }}" data-media-record="{{ e(json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}"><button type="button" class="media-selector-preview" data-media-open><img src="{{ $item['thumbnail_url'] ?? '' }}" alt="{{ $item['name'] }}"></button><span>{{ $item['name'] }}</span><button type="button" data-media-remove aria-label="{{ __('posts.remove_media') }}"><i class="ki-filled ki-trash"></i></button></article>
                @endforeach
            </div>
            <input type="hidden" name="media[{{ $collection }}]" value="{{ json_encode(collect($selectedMedia[$collection] ?? [])->pluck('id')->all()) }}">
            <button type="button" class="vuero-button taxonomy-media-picker__open" data-media-open>{{ __('posts.choose_media') }}</button>
        </section>
    @endforeach
    <section class="post-translations" aria-label="{{ __('posts.translations') }}">
        <nav class="post-locale-tabs" role="tablist">@foreach($locales as $locale => $language)<button type="button" role="tab" data-locale-tab="{{ $locale }}" aria-selected="{{ $locale === \App\Support\LocaleCatalog::resolve(app()->getLocale()) ? 'true' : 'false' }}">{{ $language['name'] ?? $locale }}</button>@endforeach</nav>
        @foreach($locales as $locale => $language)
            @php
                $translation = $record?->getTranslations($locale) ?? [];
            @endphp
            <fieldset class="post-locale-panel" data-locale-panel="{{ $locale }}" @if($locale !== \App\Support\LocaleCatalog::resolve(app()->getLocale())) hidden @endif>
                @foreach($translationFields as $field)
                    @php
                        $fieldValue = old('translation.'.$locale.'.'.$field, $translation[$field] ?? '');
                        $fieldError = 'translation.'.$locale.'.'.$field;
                        $fieldId = 'post-'.$field.'-'.$locale;
                        $fieldName = 'translation['.$locale.']['.$field.']';
                        $fieldLabel = $field === 'title' ? __('posts.title_field') : __('posts.fields.'.$field);
                        $fieldRequired = $field === 'title' && $locale === \App\Support\LocaleCatalog::resolve(app()->getLocale());
                    @endphp
                    @if($field === 'content')
                        @cmsHook('jobic.editor.render.before', ['type' => $type, 'locale' => $locale, 'post_id' => $record?->id])
                        @php
                            $fieldId = 'post-editor-'.$locale;
                            $fieldName = 'translation['.$locale.'][content]';
                            $fieldLabel = __('posts.content');
                        @endphp
                        <x-form.field variant="crud" :label="$fieldLabel" :for="$fieldId" :error="$fieldError"><div class="jobic-editor" id="{{ $fieldId }}" data-editor data-locale="{{ $locale }}" data-content="{{ e($fieldValue) }}"></div><input type="hidden" name="{{ $fieldName }}" data-editor-content="{{ $locale }}" value="{{ $fieldValue }}"><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
                        @cmsHook('jobic.editor.render.after', ['type' => $type, 'locale' => $locale, 'post_id' => $record?->id])
                    @elseif($field === 'excerpt')
                        @php
                            $fieldId = 'post-excerpt-'.$locale;
                            $fieldName = 'translation['.$locale.'][excerpt]';
                            $fieldLabel = __('posts.excerpt');
                        @endphp
                        <x-form.field variant="crud" :label="$fieldLabel" :for="$fieldId" :error="$fieldError"><textarea id="{{ $fieldId }}" name="{{ $fieldName }}">{{ $fieldValue }}</textarea><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
                    @else
                        <x-form.field variant="crud" :label="$fieldLabel" :for="$fieldId" :error="$fieldError"><x-form.input :id="$fieldId" :name="$fieldName" :value="$fieldValue" :maxlength="$field === 'title' ? 255 : 10000" :required="$fieldRequired" /><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
                    @endif
                @endforeach
            </fieldset>
        @endforeach
    </section>
    @if($record && $revisions->isNotEmpty())
        <section class="post-revisions">
            <h2>{{ __('posts.revisions') }}</h2>
            @foreach($revisions as $revision)
                <details>
                    <summary><span>{{ __('posts.revision') }} #{{ $revision->id }}</span><time>{{ \App\Support\LocalizedDate::format($revision->created_at) }}</time></summary>
                    <pre dir="ltr">{{ json_encode($revision->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    <button class="vuero-button" type="button" data-revision-restore data-url="{{ route('admin.posts.revisions.restore', ['type' => $type, 'post' => $record->id, 'revision' => $revision->id]) }}">{{ __('posts.restore_revision') }}</button>
                </details>
            @endforeach
        </section>
    @endif
    @cmsHook('jobic.admin.posts.form.after', ['type' => $type, 'post_id' => $record?->id])
</form>
<dialog class="panel-file-picker-modal" data-media-manager data-file-picker-modal data-browse-url="{{ route('admin.media-manager.browse') }}" data-upload-url="{{ route('admin.media-manager.upload') }}" data-folder-url="{{ route('admin.media-manager.folders.store') }}" data-items-url="{{ url('/admin/media-manager') }}">
    <header class="panel-file-picker-modal__head"><div><span><i class="ki-filled ki-folder"></i></span><div><strong>{{ __('posts.choose_media') }}</strong></div></div><button type="button" data-file-picker-close aria-label="{{ __('posts.cancel') }}"><i class="ki-filled ki-cross"></i></button></header>
    @include('media.workspace', ['mode' => 'picker'])
</dialog>
</section>
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('theme/vendors/keenicons/styles.bundle.css') }}">@endpush
@push('scripts')
@php
    $postEditorConfig = [
        'tools' => $editorConfig['tools'],
        'minHeight' => $editorConfig['minHeight'],
        'uploadUrl' => route('admin.media-manager.upload'),
        'messages' => __('posts.editor'),
    ];
@endphp
<script>window.JobicEditorConfig = {{ \Illuminate\Support\Js::from($postEditorConfig) }};</script>
@vite('resources/js/posts/form.js', 'vuero/build')
@endpush
