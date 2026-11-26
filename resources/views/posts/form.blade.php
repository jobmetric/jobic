@extends(($embedded ?? false) ? 'common.embedded' : 'common.admin')
@section('title', __('posts.'.($record ? ($type === 'page' ? 'edit_page' : 'edit') : ($type === 'page' ? 'create_page' : 'create'))))
@section('content')
@push('styles') @vite('resources/css/posts.css', 'vuero/build') @endpush
@php
    $workflowActive = app(\App\Services\Posts\PostLifecycleView::class)->activeDefinition($type) !== null;
    $postReadOnly = $workflowActive && $record && $record->status !== 'editable';
    $languageLocked = $workflowActive && $record && \JobMetric\Post\Models\PostLocaleLock::query()
        ->where('post_id', $record->id)->where('locale', $activeLocale)->where('locked', true)->exists();
@endphp
<form class="crud-page post-editor-page crud-form crud-language-form crud-taxonomy-form post-editor-form" data-post-form data-type="{{ $type }}" data-form-read-only="{{ $postReadOnly ? '1' : '0' }}" action="{{ $record ? route('admin.posts.update', ['type' => $type, 'post' => $record->id]) : route('admin.posts.store', ['type' => $type]) }}?locale={{ urlencode($activeLocale) }}" data-edit-lock-renew-url="{{ $editLockRenewUrl }}" data-edit-lock-release-url="{{ $editLockReleaseUrl }}" method="post">
    @csrf @if($record) @method('PUT') @endif
    @if($version)<input type="hidden" name="_version" value="{{ $version }}">@endif
    @if($editLock)<input type="hidden" name="_edit_lock" value="{{ $editLock['token'] }}">@endif
    <header class="crud-toolbar is-sticky"><div class="crud-taxonomy-title"><h1>{{ __('posts.'.($record ? ($type === 'page' ? 'edit_page' : 'edit') : ($type === 'page' ? 'create_page' : 'create'))) }}</h1></div><div data-crud-toolbar></div></header>
    <p class="crud-hint">{{ $description ?: __('posts.form_intro') }}</p>
    @cmsHook('jobic.admin.posts.form.before', ['type' => $type, 'post_id' => $record?->id])
    <div class="crud-taxonomy-layout has-sidebar"><main>
    <section class="crud-translation-group post-translation-group" aria-label="{{ __('posts.translations') }}">
        <header class="post-translation-header"><div class="post-translation-heading">@if($flag = \App\Support\LocaleCatalog::flagUrl($locales[$activeLocale]['flag'] ?? null))<img src="{{ $flag }}" alt="">@else<x-vuero.icon name="languages" size="20" />@endif<h2>{{ __('taxonomies.translation_group_line', ['language' => $locales[$activeLocale]['name'] ?? strtoupper($activeLocale)]) }}</h2>@if($postReadOnly || $languageLocked)<span class="post-translation-lock"><x-vuero.icon name="lock" size="15" />{{ $postReadOnly ? __('posts.lifecycle.post_read_only') : __('posts.lifecycle.language_read_only') }}</span>@endif</div>
            @if($hasSeo)<div class="post-form-tabs" role="tablist" aria-label="{{ __('posts.translation_sections') }}" data-post-form-tabs>
                <button type="button" id="post-content-tab-{{ $activeLocale }}" class="is-active" role="tab" aria-selected="true" aria-controls="post-content-panel-{{ $activeLocale }}" data-post-form-tab="content">{{ __('posts.content_tab') }}</button>
                <button type="button" id="post-seo-tab-{{ $activeLocale }}" role="tab" aria-selected="false" aria-controls="post-seo-panel-{{ $activeLocale }}" tabindex="-1" data-post-form-tab="seo">{{ __('posts.seo_tab') }}</button>
            </div>@endif
        </header>
        @php
            $locale = $activeLocale;
            $language = $locales[$locale] ?? [];
            $translation = $record?->getTranslations($locale) ?? [];
            $translation['content'] = app(\App\Services\Editor\EditorDocuments::class)->readForEditor($record, $locale) ?? '';
            $seoFields = ['meta_title', 'meta_description', 'meta_keywords'];
        @endphp
            <fieldset id="post-locale-{{ $locale }}" class="post-locale-panel crud-form-fields" data-locale-panel="{{ $locale }}" lang="{{ $locale }}" dir="{{ $language['direction'] ?? 'auto' }}" @disabled($postReadOnly || $languageLocked) @if($postReadOnly || $languageLocked) inert @endif>
                @if($hasSeo)
                    <div id="post-content-panel-{{ $locale }}" role="tabpanel" aria-labelledby="post-content-tab-{{ $locale }}" data-post-form-panel="content">
                        @foreach($translationFields as $field)
                            @continue(in_array($field, $seoFields, true))
                            @include('posts.partials.translation-field', ['field' => $field, 'locale' => $locale, 'translation' => $translation, 'activeLocale' => $activeLocale, 'type' => $type, 'record' => $record])
                        @endforeach
                    </div>
                    <div id="post-seo-panel-{{ $locale }}" role="tabpanel" aria-labelledby="post-seo-tab-{{ $locale }}" data-post-form-panel="seo" hidden>
                        @foreach($translationFields as $field)
                            @continue(!in_array($field, $seoFields, true))
                            @include('posts.partials.translation-field', ['field' => $field, 'locale' => $locale, 'translation' => $translation, 'activeLocale' => $activeLocale, 'type' => $type, 'record' => $record])
                        @endforeach
                    </div>
                @else
                    @foreach($translationFields as $field)
                        @include('posts.partials.translation-field', ['field' => $field, 'locale' => $locale, 'translation' => $translation, 'activeLocale' => $activeLocale, 'type' => $type, 'record' => $record])
                    @endforeach
                @endif
            </fieldset>
    </section>
    <fieldset class="crud-form-fields crud-taxonomy-main-fields" @disabled($postReadOnly) @if($postReadOnly) inert @endif>
    @php
        $statusTarget = $record ?? new \JobMetric\Post\Models\Post;
        $statusTarget->type = $type;
        $statusOptions = app(\App\Services\Posts\PostWorkflow::class)->availableStatusOptions($statusTarget, $activeLocale);
        $hasActiveWorkflow = app(\App\Services\Posts\PostLifecycleView::class)->activeDefinition($type) !== null;
    @endphp
    @if($hasActiveWorkflow)
        <input type="hidden" name="status" value="{{ $record?->status ?? 'editable' }}">
    @else
        <x-form.field variant="crud" :label="__('posts.status')" for="post-status" error="status"><x-form.select id="post-status" name="status">@foreach($statusOptions as $status => $statusLabel)<option value="{{ $status }}" @selected(old('status', $record?->status ?? 'draft') === $status)>{{ $statusLabel }}</option>@endforeach</x-form.select><span data-error-for="status" class="crud-field-error"></span></x-form.field>
    @endif
    @php
        $publishDate = $record?->published_at;
        $calendarDate = $publishDate ? \JobMetric\MultiCalendar\Factory\CalendarConverterFactory::make(\App\Support\LocaleCatalog::calendar($activeLocale))->fromGregorian($publishDate->year, $publishDate->month, $publishDate->day, '/') . ' ' . $publishDate->format('H:i') : '';
    @endphp
    @unless($lifecycle)<x-form.field variant="crud" :label="__('posts.published_at')" for="post-published-at" error="published_at"><x-form.input id="post-published-at" type="text" name="published_at" :value="old('published_at', $calendarDate)" data-post-datepicker data-calendar="{{ \App\Support\LocaleCatalog::calendar($activeLocale) }}" autocomplete="off" dir="ltr" /><span data-error-for="published_at" class="crud-field-error"></span></x-form.field>@endunless
    @if($hasComments)
        <x-form.field variant="crud" :label="__('posts.comment_status')" for="post-comment-status" error="comment_status"><x-form.select id="post-comment-status" name="comment_status"><option value="1" @selected((bool) old('comment_status', $record?->comment_status ?? true))>{{ __('posts.comments_enabled') }}</option><option value="0" @selected(!(bool) old('comment_status', $record?->comment_status ?? true))>{{ __('posts.comments_disabled') }}</option></x-form.select><span data-error-for="comment_status" class="crud-field-error"></span></x-form.field>
    @endif
    @if($hasPassword)
        <x-form.field variant="crud" :label="__('posts.password')" for="post-password" error="password">
            <x-form.password id="post-password" name="password" autocomplete="new-password" />
            <p class="crud-hint">{{ $record?->password ? __('posts.password_keep_hint') : __('posts.password_hint') }}</p>
            <span data-error-for="password" class="crud-field-error"></span>
        </x-form.field>
        @if($record?->password)
            <label class="post-password-clear"><input type="checkbox" name="clear_password" value="1" @checked(old('clear_password'))><span>{{ __('posts.password_clear') }}</span></label>
            <span data-error-for="clear_password" class="crud-field-error"></span>
        @endif
    @endif
    </fieldset>
    @php
        $visibleCustomFields = array_diff_key($customFields, ['seo_index' => true]);
    @endphp
    @if($visibleCustomFields !== [])
        <section class="crud-capability-card post-custom-fields" @if($postReadOnly) inert @endif><header><h2>{{ __('posts.custom_fields') }}</h2></header><fieldset class="crud-form-fields" @disabled($postReadOnly)>
        @foreach($visibleCustomFields as $key => $definition)
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
    @if($lifecycle)
        @include('posts.partials.lifecycle-sidebar')
    @endif
    @if($mediaCollections !== [])
    <section class="crud-capability-card post-media-field" @if($postReadOnly) inert @endif><fieldset class="post-media-controls" @disabled($postReadOnly)><div class="taxonomy-media-collections" data-post-media-fields>
    @php
        $orderedMediaCollections = collect($mediaCollections)
            ->sortBy(fn ($_definition, $collection) => ['base' => 0, 'gallery' => 1, 'seo_image' => 2][$collection] ?? 10);
    @endphp
    @foreach($orderedMediaCollections as $collection => $definition)
        @php
            $mediaGroups = collect($definition['mimeTypes'] ?? ['image', 'document', 'audio', 'video'])
                ->map(fn ($mimeType) => \Illuminate\Support\Str::before($mimeType, '/'))
                ->unique()
                ->values()
                ->all();
        @endphp
            <div class="taxonomy-media-collection">
                <h3>{{ __('posts.media.'.$collection) }}</h3>
                <x-media.selector :collection="$collection" :multiple="(bool) ($definition['multiple'] ?? false)" :groups="$mediaGroups" :selected="collect($selectedMedia[$collection] ?? [])" />
                @php
                    $itemFields = \App\Support\GalleryItemForms::fields($type, $collection);
                @endphp
                @if($itemFields !== [])
                    @include('posts.partials.media-item-form', ['fields' => $itemFields])
                    <small class="crud-field-error" data-error-for="media_item_metadata.{{ $collection }}"></small>
                @endif
                <small class="crud-field-error" data-error-for="media.{{ $collection }}"></small>
            </div>
    @endforeach
    </div></fieldset></section>
    @endif
    @if($hasSeo)
        @include('common.seo-index', ['value' => old('metadata.seo_index', $metadata['seo_index'] ?? 1), 'disabled' => $postReadOnly])
    @endif
    @if($hasUrl)<section class="crud-capability-card post-url-field" @if($postReadOnly) inert @endif>
        <header><h2>{{ __('taxonomies.url_title') }}</h2><x-vuero.icon name="link" size="22" /></header>
        <fieldset class="crud-form-fields" @disabled($postReadOnly)><x-form.field variant="crud" for="post-slug" error="slug"><div class="crud-url-field"><span dir="ltr">{{ rtrim(url($urlPrefix ?? $type), '/') }}/</span><x-form.input id="post-slug" name="slug" :value="old('slug', $record?->slug)" maxlength="255" dir="ltr" :aria-label="__('posts.url_slug')" required /></div><span data-error-for="slug" class="crud-field-error"></span></x-form.field></fieldset>
    </section>@endif
    @foreach($taxonomyOptions as $collection => $options)
        @php
            $taxonomyId = 'post-taxonomy-'.$collection;
            $taxonomyName = 'taxonomies['.$collection.'][]';
            $taxonomyLabel = $taxonomyLabels[$collection] ?? trans('posts.'.\Illuminate\Support\Str::singular($collection));
            $taxonomyError = 'taxonomies.'.$collection;
            $taxonomyMultiple = (bool) ($taxonomyTypes[$collection]['multiple'] ?? true);
            $taxonomyType = $taxonomyTypes[$collection]['type'];
            $taxonomyHierarchical = \JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($taxonomyType)->hasHierarchical();
            $selectedTaxonomyIds = array_map('intval', (array) old('taxonomies.'.$collection, $selectedTaxonomies[$collection] ?? []));
            $taxonomyDialogId = 'post-taxonomy-dialog-'.$collection;
        @endphp
        <section class="crud-capability-card post-taxonomy-field" data-taxonomy-quick-add data-url="{{ route('admin.taxonomies.store', $taxonomyType) }}" data-locale="{{ $activeLocale }}" data-collection="{{ $collection }}" data-target="{{ $taxonomyId }}" data-dialog="{{ $taxonomyDialogId }}" data-multiple="{{ $taxonomyMultiple ? 'true' : 'false' }}" data-hierarchical="{{ $taxonomyHierarchical ? 'true' : 'false' }}" @if($postReadOnly) inert @endif>
            <header><h2>{{ __($taxonomyLabel) }}</h2><button type="button" class="post-taxonomy-add-toggle" data-taxonomy-add-open aria-haspopup="dialog" aria-controls="{{ $taxonomyDialogId }}" @disabled($postReadOnly)><x-vuero.icon name="plus" size="15" /><span>{{ __('posts.add_taxonomy') }}</span></button></header>
            <fieldset class="post-taxonomy-options" id="{{ $taxonomyId }}" aria-label="{{ __($taxonomyLabel) }}" @disabled($postReadOnly)>
                @foreach($options as $option)
                    <label class="post-taxonomy-choice" data-taxonomy-choice data-taxonomy-id="{{ $option['id'] }}" data-parent-id="{{ $option['parent_id'] ?? '' }}" data-depth="{{ $option['depth'] }}">
                        <input type="{{ $taxonomyMultiple ? 'checkbox' : 'radio' }}" name="{{ $taxonomyName }}" value="{{ $option['id'] }}" @checked(in_array((int) $option['id'], $selectedTaxonomyIds, true))>
                        <span>{{ str_repeat('— ', $option['depth']) }}{{ $option['name'] }}</span>
                    </label>
                @endforeach
            </fieldset>
            <small class="crud-field-error" data-error-for="{{ $taxonomyError }}"></small>
            <dialog class="post-taxonomy-dialog" id="{{ $taxonomyDialogId }}" data-taxonomy-dialog aria-labelledby="{{ $taxonomyDialogId }}-title">
                <header><div><span class="post-taxonomy-dialog__icon"><x-vuero.icon name="plus" size="17" /></span><h3 id="{{ $taxonomyDialogId }}-title">{{ __('posts.new_taxonomy', ['name' => __($taxonomyLabel)]) }}</h3></div><button type="button" data-taxonomy-dialog-close aria-label="{{ __('posts.cancel') }}"><x-vuero.icon name="x" size="16" /></button></header>
                <div class="post-taxonomy-dialog__body">
                    <x-form.field variant="crud" :label="__('taxonomies.name')" :for="$taxonomyDialogId.'-name'" error="taxonomy_name"><x-form.input :id="$taxonomyDialogId.'-name'" :name="$taxonomyDialogId.'-name'" maxlength="255" data-taxonomy-name autocomplete="off" required /><small class="crud-field-error" data-taxonomy-name-error></small></x-form.field>
                    @if($taxonomyHierarchical)
                        <x-form.field variant="crud" :label="__('taxonomies.parent')" :for="$taxonomyDialogId.'-parent'"><x-form.select :id="$taxonomyDialogId.'-parent'" name="taxonomy_parent_picker" data-taxonomy-parent><option value="">{{ __('taxonomies.no_parent') }}</option>@foreach($options as $option)<option value="{{ $option['id'] }}" data-depth="{{ $option['depth'] }}">{{ str_repeat('— ', $option['depth']) }}{{ $option['name'] }}</option>@endforeach</x-form.select><small class="crud-field-error" data-taxonomy-parent-error></small></x-form.field>
                    @endif
                </div>
                <footer><button type="button" class="vuero-button" data-taxonomy-dialog-close>{{ __('posts.cancel') }}</button><button type="button" class="vuero-button is-primary" data-taxonomy-create>{{ __('posts.add_taxonomy') }}</button></footer>
            </dialog>
        </section>
    @endforeach
    @if($record)<section class="crud-taxonomy-dates"><div><x-vuero.icon name="calendar" size="20" /><span>{{ __('taxonomies.created_at') }}</span><strong>{{ \App\Support\LocalizedDate::format($record->created_at) }}</strong></div><div><x-vuero.icon name="rotate" size="20" /><span>{{ __('taxonomies.updated_at') }}</span><strong>{{ \App\Support\LocalizedDate::format($record->updated_at) }}</strong></div></section>@endif
    </aside></div>
    @if($lifecycle && app(\App\Support\AdminAccess::class)->allowsAny(auth()->user(), array_intersect_key(app(\App\Support\PostWorkflowPermissions::class)->for($type), array_flip(['view', 'manage']))))
        @include('posts.partials.lifecycle-preview')
    @endif
    @if($record)
        @include('posts.partials.history-tabs')
    @else
        <template data-record-history-template>@include('posts.partials.history-tabs')</template>
    @endif
    @cmsHook('jobic.admin.posts.form.after', ['type' => $type, 'post_id' => $record?->id])
</form>
@include('posts.partials.media-dialog')
@include('common.crud.dialog')
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('theme/vendors/keenicons/styles.bundle.css') }}">@endpush
@push('scripts')
@vite('resources/js/posts/form.js', 'vuero/build')
@endpush
