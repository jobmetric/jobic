@extends('common.admin')
@section('title', $record ? __('taxonomies.edit') : __('taxonomies.create'))
@section('content')
<form class="crud-page crud-language-form crud-taxonomy-form" data-taxonomy-form method="POST" enctype="multipart/form-data" action="{{ $record ? route('admin.taxonomies.update', [$type, $record->id]) : route('admin.taxonomies.store', $type) }}">
    @csrf @if($record) @method('PUT') @endif
    @cmsHook('jobic.admin.taxonomies.form.page.before', ['type'=>$type, 'id'=>$record?->id])
    <header class="crud-toolbar is-sticky"><div class="crud-taxonomy-title"><h1>{{ $builder->getLabel() }} — {{ $record ? __('taxonomies.edit') : __('taxonomies.create') }}</h1></div><div data-crud-toolbar></div></header>
    <p class="crud-hint">{{ $builder->getDescription() }}</p>
    @cmsHook('jobic.admin.taxonomies.form.fields.before', ['type'=>$type, 'id'=>$record?->id])
    <div class="crud-taxonomy-layout @if($record || $builder->hasUrl() || $builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty()) has-sidebar @endif"><main>
        <section class="crud-translation-group"><header>@if($languages[$activeLocale]['flag_url'] ?? null)<img src="{{ $languages[$activeLocale]['flag_url'] }}" alt="">@else<x-vuero.icon name="languages" size="20" />@endif<h2>{{ __('taxonomies.translation_group_line', ['language' => $languages[$activeLocale]['name'] ?? strtoupper($activeLocale)]) }}</h2></header><fieldset class="crud-form-fields">
            @include('taxonomies.partials.translation-fields', ['locale' => $activeLocale])
        </fieldset></section>
        <fieldset class="crud-form-fields crud-taxonomy-main-fields">
            @if($builder->hasHierarchical())<x-form.field variant="crud" :label="__('taxonomies.parent')" for="taxonomy-parent" error="parent_id"><x-form.select id="taxonomy-parent" name="parent_id"><option value="">{{ __('taxonomies.no_parent') }}</option>@foreach($parents as $parent)<option value="{{ $parent['id'] }}" @selected((string) old('parent_id', $record?->parent_id) === (string) $parent['id'])>{{ str_repeat('— ', $parent['depth']) }}{{ $parent['name'] }}</option>@endforeach</x-form.select></x-form.field>@endif
            <x-form.field variant="crud" :label="__('taxonomies.ordering')" for="taxonomy-ordering" error="ordering"><x-form.input id="taxonomy-ordering" name="ordering" type="number" :value="old('ordering', $record?->ordering ?? 0)" min="0" required /></x-form.field>
            <x-form.field variant="crud" :label="__('taxonomies.status')" for="taxonomy-status" error="status"><x-form.select id="taxonomy-status" name="status"><option value="1" @selected(old('status', $record?->status ?? 1))>{{ __('taxonomies.active') }}</option><option value="0" @selected(!old('status', $record?->status ?? 1))>{{ __('taxonomies.inactive') }}</option></x-form.select></x-form.field>
        </fieldset>
        @cmsHook('jobic.admin.taxonomies.form.main.after', ['type'=>$type, 'id'=>$record?->id])
    </main><aside>
    @if($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty())
    <section class="crud-capability-card"><header><h2>{{ __('taxonomies.media_title') }}</h2><x-vuero.icon name="image" size="22" /></header><fieldset class="crud-form-fields">
        @php($mediaFields = collect($builder->hasBaseMedia() ? [['collection'=>'base','multiple'=>false,'mime'=>['image']]] : [])->concat($builder->getMedia()->map(fn($item)=>['collection'=>$item->getCollection(),'multiple'=>$item->getMultiple(),'mime'=>$item->getMimeTypes()])))
        @foreach($mediaFields as $mediaField)<x-form.field variant="crud" :label="ucfirst($mediaField['collection'])" :for="'media-'.$mediaField['collection']" :error="'media_upload.'.$mediaField['collection']">@if($loop->first && $mediaPreview)<img class="crud-media-preview" src="{{ $mediaPreview }}" alt="">@endif<input class="input" id="media-{{ $mediaField['collection'] }}" type="file" name="media_upload[{{ $mediaField['collection'] }}]{{ $mediaField['multiple'] ? '[]' : '' }}" accept="image/*" @if($mediaField['multiple']) multiple @endif></x-form.field>@endforeach
    </fieldset></section>
    @endif
    @if($builder->hasUrl())<section class="crud-capability-card"><header><div><h2>{{ __('taxonomies.url_title') }}</h2><p>{{ __('taxonomies.url_hint') }}</p></div><x-vuero.icon name="link" size="22" /></header><fieldset class="crud-form-fields"><x-form.field variant="crud" :label="__('taxonomies.slug')" for="taxonomy-slug" error="slug"><div class="crud-url-field"><span>{{ url('/') }}/</span><x-form.input id="taxonomy-slug" name="slug" :value="old('slug', $record?->slugByCollection($type, true))" maxlength="255" dir="ltr" /></div></x-form.field></fieldset></section>@endif
    @if($record)<section class="crud-taxonomy-dates"><div><x-vuero.icon name="calendar" size="20" /><span>{{ __('taxonomies.created_at') }}</span><strong>{{ $record->created_at?->translatedFormat('Y/m/d H:i') }}</strong></div><div><x-vuero.icon name="rotate" size="20" /><span>{{ __('taxonomies.updated_at') }}</span><strong>{{ $record->updated_at?->translatedFormat('Y/m/d H:i') }}</strong></div></section>@endif
    </aside></div>
    @cmsHook('jobic.admin.taxonomies.form.fields.after', ['type'=>$type, 'id'=>$record?->id])
    @cmsHook('jobic.admin.taxonomies.form.page.after', ['type'=>$type, 'id'=>$record?->id])
</form>
@include('common.crud.dialog')
@cmsHook('jobic.admin.taxonomies.history.before', ['type'=>$type, 'id'=>$record?->id])
<section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
    <header><h2>{{ __('taxonomies.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('taxonomies.history_refresh') }}</button></header>
    <p class="crud-hint">{{ __('taxonomies.history_hint') }}</p>
    <div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('taxonomies.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('taxonomies.next') }}</button></footer>
</section>
@cmsHook('jobic.admin.taxonomies.history.after', ['type'=>$type, 'id'=>$record?->id])
@endsection
@push('scripts') @vite('resources/js/taxonomies/form.js', 'vuero/build') @endpush
