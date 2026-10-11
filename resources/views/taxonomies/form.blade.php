@extends('common.admin')
@section('title', $record ? __('taxonomies.edit') : __('taxonomies.create'))
@section('content')
<form class="crud-page crud-language-form crud-taxonomy-form" data-taxonomy-form method="POST" enctype="multipart/form-data" action="{{ $record ? route('admin.taxonomies.update', [$type, $record->id]) : route('admin.taxonomies.store', $type) }}" @if($editLock) data-edit-lock-renew-url="{{ $editLockRenewUrl }}" data-edit-lock-release-url="{{ $editLockReleaseUrl }}" @endif>
    @csrf @if($record) @method('PUT') @endif
    @if($version)<input type="hidden" name="_version" value="{{ $version }}">@endif
    @if($editLock)<input type="hidden" name="_edit_lock" value="{{ $editLock['token'] }}">@endif
    @cmsHook('jobic.admin.taxonomies.form.page.before', ['type'=>$type, 'id'=>$record?->id])
    <header class="crud-toolbar is-sticky"><div class="crud-taxonomy-title"><h1>{{ $builder->getLabel() }} — {{ $record ? __('taxonomies.edit') : __('taxonomies.create') }}</h1></div><div data-crud-toolbar></div></header>
    <p class="crud-hint">{{ $builder->getDescription() }}</p>
    @cmsHook('jobic.admin.taxonomies.form.fields.before', ['type'=>$type, 'id'=>$record?->id])
    <div class="crud-taxonomy-layout @if($record || $builder->hasUrl() || $builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty() || $builder->isSeoEnabled()) has-sidebar @endif"><main>
        <section class="crud-translation-group taxonomy-translation-group"><header class="taxonomy-translation-header"><div class="taxonomy-translation-heading">@if($languages[$activeLocale]['flag_url'] ?? null)<img src="{{ $languages[$activeLocale]['flag_url'] }}" alt="">@else<x-vuero.icon name="languages" size="20" />@endif<h2>{{ __('taxonomies.translation_group_line', ['language' => $languages[$activeLocale]['name'] ?? strtoupper($activeLocale)]) }}</h2></div>
            @if($builder->isSeoEnabled())<div class="taxonomy-form-tabs" role="tablist" aria-label="{{ __('taxonomies.translation_sections') }}" data-taxonomy-form-tabs>
                <button type="button" id="taxonomy-content-tab" class="is-active" role="tab" aria-selected="true" aria-controls="taxonomy-content-panel" data-taxonomy-form-tab="content">{{ __('taxonomies.content_tab') }}</button>
                <button type="button" id="taxonomy-seo-tab" role="tab" aria-selected="false" aria-controls="taxonomy-seo-panel" tabindex="-1" data-taxonomy-form-tab="seo">{{ __('taxonomies.seo_tab') }}</button>
            </div>@endif
        </header><fieldset class="crud-form-fields taxonomy-locale-panel" lang="{{ $activeLocale }}" dir="{{ $languages[$activeLocale]['direction'] ?? 'auto' }}">
            @if($builder->isSeoEnabled())
                <div id="taxonomy-content-panel" role="tabpanel" aria-labelledby="taxonomy-content-tab" data-taxonomy-form-panel="content">@include('taxonomies.partials.translation-fields', ['locale' => $activeLocale, 'section' => 'content'])</div>
                <div id="taxonomy-seo-panel" role="tabpanel" aria-labelledby="taxonomy-seo-tab" data-taxonomy-form-panel="seo" hidden>
                    @include('taxonomies.partials.translation-fields', ['locale' => $activeLocale, 'section' => 'seo'])
                </div>
            @else
                @include('taxonomies.partials.translation-fields', ['locale' => $activeLocale])
            @endif
        </fieldset></section>
        <fieldset class="crud-form-fields crud-taxonomy-main-fields">
            @if($builder->hasHierarchical())<x-form.field variant="crud" :label="__('taxonomies.parent')" for="taxonomy-parent" error="parent_id"><x-form.select id="taxonomy-parent" name="parent_id"><option value="">{{ __('taxonomies.no_parent') }}</option>@foreach($parents as $parent)<option value="{{ $parent['id'] }}" @selected((string) old('parent_id', $record?->parent_id) === (string) $parent['id'])>{{ str_repeat('— ', $parent['depth']) }}{{ $parent['name'] }}</option>@endforeach</x-form.select></x-form.field>@endif
            <x-form.field variant="crud" :label="__('taxonomies.ordering')" for="taxonomy-ordering" error="ordering"><x-form.input id="taxonomy-ordering" name="ordering" type="number" :value="old('ordering', $record?->ordering ?? 0)" min="0" required /></x-form.field>
            <x-form.field variant="crud" :label="__('taxonomies.status')" for="taxonomy-status" error="status"><x-form.select id="taxonomy-status" name="status"><option value="1" @selected(old('status', $record?->status ?? 1))>{{ __('taxonomies.active') }}</option><option value="0" @selected(!old('status', $record?->status ?? 1))>{{ __('taxonomies.inactive') }}</option></x-form.select></x-form.field>
        </fieldset>
        @include('taxonomies.partials.capabilities')
        @cmsHook('jobic.admin.taxonomies.form.main.after', ['type'=>$type, 'id'=>$record?->id])
    </main><aside>
    @if($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty())
    <section class="crud-capability-card"><div class="taxonomy-media-collections" data-taxonomy-media-fields>
        @php($mediaFields = collect($builder->hasBaseMedia() ? [['collection'=>'base','multiple'=>false,'mime'=>['image']]] : [])->concat($builder->getMedia()->map(fn($item)=>['collection'=>$item->getCollection(),'multiple'=>$item->getMultiple(),'mime'=>$item->getMimeTypes()])))
        @foreach($mediaFields as $mediaField)
            @php($selectedMedia = collect($mediaSelections[$mediaField['collection']] ?? []))
            <div class="taxonomy-media-collection">
                <h3>{{ in_array($mediaField['collection'], ['base', 'gallery', 'seo_image']) ? __('taxonomies.media_'.$mediaField['collection']) : __($mediaField['collection']) }}</h3>
                <x-media.selector :collection="$mediaField['collection']" :multiple="$mediaField['multiple']" :groups="$mediaField['mime']" :selected="$selectedMedia" />
            <small class="crud-field-error" data-error-for="media_selection.{{ $mediaField['collection'] }}"></small></div>
        @endforeach
    </div></section>
    @endif
    @if($builder->isSeoEnabled())
        @include('common.seo-index', ['value' => old('metadata.seo_index', $capabilityValues['seo_index'] ?? 1)])
    @endif
    @if($builder->hasUrl())
    <section class="crud-capability-card">
        <header><h2>{{ __('taxonomies.url_title') }}</h2><x-vuero.icon name="link" size="22" /></header>
        <fieldset class="crud-form-fields">
            <x-form.field variant="crud" :label="__('taxonomies.slug')" for="taxonomy-slug" error="slug">
                <div class="crud-url-field">
                    <span dir="ltr">{{ rtrim(url($builder->getUrlPrefix()), '/') }}/</span>
                    <x-form.input id="taxonomy-slug" name="slug" :value="old('slug', $record?->slugByCollection($type, true))" maxlength="255" dir="ltr" />
                </div>
            </x-form.field>
        </fieldset>
    </section>
    @endif
    @if($record)<section class="crud-taxonomy-dates"><div><x-vuero.icon name="calendar" size="20" /><span>{{ __('taxonomies.created_at') }}</span><strong>{{ \App\Support\LocalizedDate::format($record->created_at) }}</strong></div><div><x-vuero.icon name="rotate" size="20" /><span>{{ __('taxonomies.updated_at') }}</span><strong>{{ \App\Support\LocalizedDate::format($record->updated_at) }}</strong></div></section>@endif
    </aside></div>
    @cmsHook('jobic.admin.taxonomies.form.fields.after', ['type'=>$type, 'id'=>$record?->id])
    @cmsHook('jobic.admin.taxonomies.form.page.after', ['type'=>$type, 'id'=>$record?->id])
</form>
@include('common.crud.dialog')
@if($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty())
<dialog class="panel-file-picker-modal" data-media-manager data-file-picker-modal
    data-browse-url="{{ route('admin.media-manager.browse') }}" data-upload-url="{{ route('admin.media-manager.upload') }}"
    data-folder-url="{{ route('admin.media-manager.folders.store') }}" data-items-url="{{ url('/admin/media-manager') }}">
    <header class="panel-file-picker-modal__head"><div><span><x-vuero.icon name="folder" size="18" /></span><div><strong>{{ __('taxonomies.media_manager_title') }}</strong></div></div><button type="button" data-file-picker-close aria-label="{{ __('taxonomies.cancel') }}"><x-vuero.icon name="x" size="16" /></button></header>
    @include('media.workspace', ['mode' => 'picker'])
</dialog>
@endif
@cmsHook('jobic.admin.taxonomies.history.before', ['type'=>$type, 'id'=>$record?->id])
<section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
    <header><h2>{{ __('taxonomies.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('taxonomies.history_refresh') }}</button></header>
    <p class="crud-hint">{{ __('taxonomies.history_hint') }}</p>
    <div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('taxonomies.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('taxonomies.next') }}</button></footer>
</section>
@cmsHook('jobic.admin.taxonomies.history.after', ['type'=>$type, 'id'=>$record?->id])
@endsection
@push('styles')<link rel="stylesheet" href="{{ asset('theme/vendors/keenicons/styles.bundle.css') }}">@endpush
@push('scripts') @vite('resources/js/taxonomies/form.js', 'vuero/build') @endpush
