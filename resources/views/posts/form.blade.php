@extends('common.admin')
@section('title', __('posts.'.($record ? ($type === 'page' ? 'edit_page' : 'edit') : ($type === 'page' ? 'create_page' : 'create'))))
@section('content')
@push('styles') @vite('resources/css/posts.css', 'vuero/build') @endpush
<form class="crud-page post-editor-page crud-form crud-language-form crud-taxonomy-form post-editor-form" data-post-form data-type="{{ $type }}" action="{{ $record ? route('admin.posts.update', ['type' => $type, 'post' => $record->id]) : route('admin.posts.store', ['type' => $type]) }}?locale={{ urlencode($activeLocale) }}" method="post">
    @csrf @if($record) @method('PUT') @endif
    @if($version)<input type="hidden" name="_version" value="{{ $version }}">@endif
    <header class="crud-toolbar is-sticky"><div class="crud-taxonomy-title"><h1>{{ __('posts.'.($record ? ($type === 'page' ? 'edit_page' : 'edit') : ($type === 'page' ? 'create_page' : 'create'))) }}</h1></div><div data-crud-toolbar></div></header>
    <p class="crud-hint">{{ $description ? __($description) : __('posts.form_intro') }}</p>
    @cmsHook('jobic.admin.posts.form.before', ['type' => $type, 'post_id' => $record?->id])
    <div class="crud-taxonomy-layout has-sidebar"><main>
    <section class="crud-translation-group post-translation-group" aria-label="{{ __('posts.translations') }}">
        <header><x-vuero.icon name="languages" size="20" /><h2>{{ __('posts.translations') }}</h2></header>
        <div class="post-active-locale">@if($flag = \App\Support\LocaleCatalog::flagUrl($locales[$activeLocale]['flag'] ?? null))<img src="{{ $flag }}" alt="">@endif<span>{{ $locales[$activeLocale]['name'] ?? strtoupper($activeLocale) }}</span></div>
        @php
            $locale = $activeLocale;
            $language = $locales[$locale] ?? [];
            $translation = $record?->getTranslations($locale) ?? [];
        @endphp
            <fieldset id="post-locale-{{ $locale }}" class="post-locale-panel crud-form-fields" data-locale-panel="{{ $locale }}" lang="{{ $locale }}" dir="{{ $language['direction'] ?? 'auto' }}">
                @foreach($translationFields as $field)
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
                    @elseif($field === 'excerpt')
                        <x-form.field variant="crud" :label="__('posts.excerpt')" for="post-excerpt-{{ $locale }}" :error="$fieldError"><x-form.textarea id="post-excerpt-{{ $locale }}" :name="$fieldName" :value="$fieldValue" /><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
                    @else
                        <x-form.field variant="crud" :label="$fieldLabel" :for="$fieldId" :error="$fieldError"><x-form.input :id="$fieldId" :name="$fieldName" :value="$fieldValue" :maxlength="$field === 'title' ? 255 : 10000" :required="$fieldRequired" /><span data-error-for="{{ $fieldError }}" class="crud-field-error"></span></x-form.field>
                    @endif
                @endforeach
            </fieldset>
    </section>
    <fieldset class="crud-form-fields crud-taxonomy-main-fields">
    @php
        $statusTarget = $record ?? new \JobMetric\Post\Models\Post;
        $statusTarget->type = $type;
        $statusOptions = app(\App\Services\Posts\PostWorkflow::class)->availableStatusOptions($statusTarget, $activeLocale);
        $statusOptions = $statusOptions ?: __('posts.statuses');
    @endphp
    <x-form.field variant="crud" :label="__('posts.status')" for="post-status" error="status"><x-form.select id="post-status" name="status">@foreach($statusOptions as $status => $statusLabel)<option value="{{ $status }}" @selected(old('status', $record?->status ?? 'draft') === $status)>{{ $statusLabel }}</option>@endforeach</x-form.select><span data-error-for="status" class="crud-field-error"></span></x-form.field>
    @php
        $publishDate = $record?->published_at;
        $calendarDate = $publishDate ? \JobMetric\MultiCalendar\Factory\CalendarConverterFactory::make(\App\Support\LocaleCatalog::calendar($activeLocale))->fromGregorian($publishDate->year, $publishDate->month, $publishDate->day, '/') . ' ' . $publishDate->format('H:i') : '';
    @endphp
    <x-form.field variant="crud" :label="__('posts.published_at')" for="post-published-at" error="published_at"><x-form.input id="post-published-at" type="text" name="published_at" :value="old('published_at', $calendarDate)" data-post-datepicker data-calendar="{{ \App\Support\LocaleCatalog::calendar($activeLocale) }}" autocomplete="off" dir="ltr" /><span data-error-for="published_at" class="crud-field-error"></span></x-form.field>
    @if($hasComments)
        <x-form.field variant="crud" :label="__('posts.comment_status')" for="post-comment-status" error="comment_status"><x-form.select id="post-comment-status" name="comment_status"><option value="1" @selected((bool) old('comment_status', $record?->comment_status ?? true))>{{ __('posts.comments_enabled') }}</option><option value="0" @selected(!(bool) old('comment_status', $record?->comment_status ?? true))>{{ __('posts.comments_disabled') }}</option></x-form.select><span data-error-for="comment_status" class="crud-field-error"></span></x-form.field>
    @endif
    </fieldset>
    @if($customFields !== [])
        <section class="crud-capability-card post-custom-fields"><header><h2>{{ __('posts.custom_fields') }}</h2></header><fieldset class="crud-form-fields">
        @foreach($customFields as $key => $definition)
            @php
                $metaId = 'post-meta-'.$key;
                $metaName = 'metadata['.$key.']';
                $metaError = 'metadata.'.$key;
                $metaType = ($definition['type'] ?? 'text') === 'number' ? 'number' : 'text';
                $metaValue = old($metaError, $metadata[$key] ?? '');
            @endphp
            <x-form.field variant="crud" :label="__($definition['label'] ?? $key)" :for="$metaId" :error="$metaError"><x-form.input :id="$metaId" :name="$metaName" :type="$metaType" :value="$metaValue" /><span data-error-for="{{ $metaError }}" class="crud-field-error"></span></x-form.field>
        @endforeach
        </fieldset></section>
    @endif
    </main><aside>
    @foreach($mediaCollections as $collection => $definition)
        @php
            $mediaGroups = collect($definition['mimeTypes'] ?? ['image', 'document', 'audio', 'video'])
                ->map(fn ($mimeType) => \Illuminate\Support\Str::before($mimeType, '/'))
                ->unique()
                ->values()
                ->all();
        @endphp
        <section class="crud-capability-card post-media-field">
            <header><h2>{{ __('posts.media.'.$collection) }}</h2></header>
            <div class="taxonomy-media-collections" data-post-media-fields><div class="taxonomy-media-collection">
                <x-media.selector :collection="$collection" :multiple="(bool) ($definition['multiple'] ?? false)" :groups="collect($definition['mimeTypes'] ?? ['image', 'document', 'audio', 'video'])->map(fn ($mimeType) => \Illuminate\Support\Str::before($mimeType, '/'))->unique()->values()->all()" :selected="collect($selectedMedia[$collection] ?? [])" />
                <small class="crud-field-error" data-error-for="media.{{ $collection }}"></small>
            </div></div>
        </section>
    @endforeach
    @if($hasUrl)<section class="crud-capability-card post-url-field">
        <header><h2>{{ __('taxonomies.url_title') }}</h2><x-vuero.icon name="link" size="22" /></header>
        <fieldset class="crud-form-fields"><x-form.field variant="crud" :label="__('posts.url_slug')" for="post-slug" error="slug"><div class="crud-url-field"><span dir="ltr">{{ rtrim(url($urlPrefix ?? $type), '/') }}/</span><x-form.input id="post-slug" name="slug" :value="old('slug', $record?->slug)" maxlength="255" dir="ltr" required /></div><span data-error-for="slug" class="crud-field-error"></span></x-form.field></fieldset>
    </section>@endif
    @foreach($taxonomyOptions as $collection => $options)
        @php
            $taxonomyId = 'post-taxonomy-'.$collection;
            $taxonomyName = 'taxonomies['.$collection.'][]';
            $taxonomyLabel = $taxonomyLabels[$collection] ?? trans('posts.'.\Illuminate\Support\Str::singular($collection));
            $taxonomyError = 'taxonomies.'.$collection;
            $taxonomyMultiple = (bool) ($taxonomyTypes[$collection]['multiple'] ?? true);
        @endphp
        <section class="crud-capability-card post-taxonomy-field"><header><h2>{{ __($taxonomyLabel) }}</h2></header><fieldset class="crud-form-fields"><x-form.field variant="crud" :label="$taxonomyLabel" :for="$taxonomyId" :error="$taxonomyError"><x-form.select :id="$taxonomyId" :name="$taxonomyName" :multiple="$taxonomyMultiple">@foreach($options as $option)<option value="{{ $option['id'] }}" @selected(in_array($option['id'], old('taxonomies.'.$collection, $selectedTaxonomies[$collection] ?? [])))>{{ $option['name'] }}</option>@endforeach</x-form.select><span data-error-for="{{ $taxonomyError }}" class="crud-field-error"></span></x-form.field></fieldset><div class="post-taxonomy-quick-add" data-taxonomy-quick-add data-url="{{ route('admin.taxonomies.store', $taxonomyTypes[$collection]['type']) }}" data-locale="{{ $activeLocale }}" data-target="{{ $taxonomyId }}"><input type="text" maxlength="255" aria-label="{{ __('posts.new_taxonomy', ['name' => __($taxonomyLabel)]) }}" placeholder="{{ __('posts.new_taxonomy', ['name' => __($taxonomyLabel)]) }}"><button type="button" class="vuero-button" data-taxonomy-add>{{ __('posts.add_taxonomy') }}</button><small class="crud-field-error" data-taxonomy-add-error></small></div></section>
    @endforeach
    @if($record)<section class="crud-taxonomy-dates"><div><x-vuero.icon name="calendar" size="20" /><span>{{ __('taxonomies.created_at') }}</span><strong>{{ \App\Support\LocalizedDate::format($record->created_at) }}</strong></div><div><x-vuero.icon name="rotate" size="20" /><span>{{ __('taxonomies.updated_at') }}</span><strong>{{ \App\Support\LocalizedDate::format($record->updated_at) }}</strong></div></section>@endif
    </aside></div>
    <section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
        <header><h2>{{ __('posts.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('posts.history_refresh') }}</button></header>
        <p class="crud-hint">{{ __('posts.history_hint') }}</p>
        <div data-history-entries aria-live="polite"></div>
        <footer><button type="button" class="vuero-button" data-history-previous>{{ __('posts.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('posts.next') }}</button></footer>
    </section>
    @if($record && $revisions->isNotEmpty())
        <section class="crud-history post-revisions"><header><h2>{{ __('posts.revisions') }}</h2></header>
            @foreach($revisions as $revision)<details class="crud-help-section"><summary><span>{{ __('posts.revision') }} #{{ $revision->id }}</span><time>{{ \App\Support\LocalizedDate::format($revision->created_at) }}</time></summary><pre dir="ltr">{{ json_encode($revision->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre><button class="vuero-button" type="button" data-revision-restore data-url="{{ route('admin.posts.revisions.restore', ['type' => $type, 'post' => $record->id, 'revision' => $revision->id]) }}">{{ __('posts.restore_revision') }}</button></details>@endforeach
        </section>
    @endif
    @cmsHook('jobic.admin.posts.form.after', ['type' => $type, 'post_id' => $record?->id])
</form>
<dialog class="panel-file-picker-modal" data-media-manager data-file-picker-modal data-browse-url="{{ route('admin.media-manager.browse') }}" data-upload-url="{{ route('admin.media-manager.upload') }}" data-folder-url="{{ route('admin.media-manager.folders.store') }}" data-items-url="{{ url('/admin/media-manager') }}">
    <header class="panel-file-picker-modal__head"><div><span><i class="ki-filled ki-folder"></i></span><div><strong>{{ __('posts.choose_media') }}</strong></div></div><button type="button" data-file-picker-close aria-label="{{ __('posts.cancel') }}"><i class="ki-filled ki-cross"></i></button></header>
    @include('media.workspace', ['mode' => 'picker'])
</dialog>
@include('common.crud.dialog')
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('theme/vendors/keenicons/styles.bundle.css') }}">@endpush
@push('scripts')
@vite('resources/js/posts/form.js', 'vuero/build')
@endpush
