@extends('common.admin')
@section('title', $record ? __('taxonomies.edit') : __('taxonomies.create'))
@section('content')
<form class="crud-page" data-taxonomy-form method="POST" enctype="multipart/form-data" action="{{ $record ? route('admin.taxonomies.update', [$type, $record->id]) : route('admin.taxonomies.store', $type) }}">
    @csrf @if($record) @method('PUT') @endif
    @cmsHook('jobic.admin.taxonomies.form.page.before', ['type'=>$type, 'id'=>$record?->id])
    <header class="crud-toolbar is-sticky"><div><h1>{{ $builder->getLabel() }} — {{ $record ? __('taxonomies.edit') : __('taxonomies.create') }}</h1></div><div data-crud-toolbar></div></header>
    <p class="crud-hint">{{ $builder->getDescription() }}</p>
    @cmsHook('jobic.admin.taxonomies.form.fields.before', ['type'=>$type, 'id'=>$record?->id])
    <section class="crud-capability-card">
        <header><div><h2>{{ __('taxonomies.general') }}</h2><p>{{ __('taxonomies.general_hint', ['language' => $languages[$activeLocale]['name'] ?? strtoupper($activeLocale)]) }}</p></div>@if(count($languages) > 1)<button type="button" class="vuero-button" data-open-translations><x-vuero.icon name="languages" size="18" />{{ __('taxonomies.other_languages') }}</button>@endif</header>
        <fieldset class="crud-form-fields">
            @include('taxonomies.partials.translation-fields', ['locale' => $activeLocale])
            @if($builder->hasHierarchical())<x-form.field variant="crud" :label="__('taxonomies.parent')" for="taxonomy-parent" error="parent_id"><x-form.select id="taxonomy-parent" name="parent_id"><option value="">{{ __('taxonomies.no_parent') }}</option>@foreach($parents as $parent)@if($parent->id !== $record?->id)<option value="{{ $parent->id }}" @selected((string) old('parent_id', $record?->parent_id) === (string) $parent->id)>{{ \App\Support\TaxonomyPresentation::name($parent) }}</option>@endif @endforeach</x-form.select></x-form.field>@endif
            <x-form.field variant="crud" :label="__('taxonomies.ordering')" for="taxonomy-ordering" error="ordering"><x-form.input id="taxonomy-ordering" name="ordering" type="number" :value="old('ordering', $record?->ordering ?? 0)" min="0" required /></x-form.field>
            <x-form.field variant="crud" :label="__('taxonomies.status')" for="taxonomy-status" error="status"><x-form.select id="taxonomy-status" name="status"><option value="1" @selected(old('status', $record?->status ?? 1))>{{ __('taxonomies.active') }}</option><option value="0" @selected(!old('status', $record?->status ?? 1))>{{ __('taxonomies.inactive') }}</option></x-form.select></x-form.field>
        </fieldset>
    </section>
    @if($builder->hasUrl())<section class="crud-capability-card"><header><div><h2>{{ __('taxonomies.url_title') }}</h2><p>{{ __('taxonomies.url_hint') }}</p></div><x-vuero.icon name="link" size="22" /></header><fieldset class="crud-form-fields"><x-form.field variant="crud" :label="__('taxonomies.slug')" for="taxonomy-slug" error="slug"><div class="crud-url-field"><span>{{ url('/') }}/</span><x-form.input id="taxonomy-slug" name="slug" :value="old('slug', $record?->urlByCollection($type, true))" maxlength="255" dir="ltr" /></div></x-form.field></fieldset></section>@endif
    @if($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty())
    <section class="crud-capability-card"><header><div><h2>{{ __('taxonomies.media_title') }}</h2><p>{{ __('taxonomies.media_hint') }}</p></div><x-vuero.icon name="image" size="22" /></header><fieldset class="crud-form-fields">
        @php($mediaFields = collect($builder->hasBaseMedia() ? [['collection'=>'base','multiple'=>false,'mime'=>['image']]] : [])->concat($builder->getMedia()->map(fn($item)=>['collection'=>$item->getCollection(),'multiple'=>$item->getMultiple(),'mime'=>$item->getMimeTypes()])))
        @foreach($mediaFields as $mediaField)<x-form.field variant="crud" :label="ucfirst($mediaField['collection'])" :for="'media-'.$mediaField['collection']" :error="'media_upload.'.$mediaField['collection']"><input class="input" id="media-{{ $mediaField['collection'] }}" type="file" name="media_upload[{{ $mediaField['collection'] }}]{{ $mediaField['multiple'] ? '[]' : '' }}" accept="image/*" @if($mediaField['multiple']) multiple @endif></x-form.field>@endforeach
    </fieldset></section>
    @endif
    @cmsHook('jobic.admin.taxonomies.form.fields.after', ['type'=>$type, 'id'=>$record?->id])
    @cmsHook('jobic.admin.taxonomies.form.page.after', ['type'=>$type, 'id'=>$record?->id])
    @if(count($languages) > 1)<dialog class="crud-filter-dialog crud-translation-dialog" data-translation-dialog><header><h2>{{ __('taxonomies.other_languages') }}</h2><button class="crud-icon-button" type="button" data-close-translations><x-vuero.icon name="x" /></button></header><div class="crud-translation-locales">@foreach($languages as $locale => $language)@continue($locale === $activeLocale)<section><h3>{{ $language['name'] }} <small>{{ strtoupper($locale) }}</small></h3><fieldset class="crud-form-fields">@include('taxonomies.partials.translation-fields', ['locale' => $locale])</fieldset></section>@endforeach</div><footer><button class="vuero-button is-primary" type="button" data-close-translations>{{ __('taxonomies.done') }}</button></footer></dialog>@endif
</form>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/taxonomies/form.js', 'vuero/build') @endpush
