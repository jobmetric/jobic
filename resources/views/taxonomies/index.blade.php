@extends('common.admin')
@php($builder = \JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($type))
@section('title', $builder->getLabel())
@section('content')
<section class="crud-page" data-taxonomy-list data-taxonomy-type="{{ $type }}">
    @cmsHook('jobic.admin.taxonomies.list.page.before', ['type' => $type])
    <header class="crud-toolbar"><div><h1>{{ $builder->getLabel() }}</h1><p>{{ $builder->getDescription() }}</p></div><div data-crud-toolbar data-list-controls></div></header>
    @cmsHook('jobic.admin.taxonomies.list.toolbar.after', ['type' => $type])
    <div class="crud-list-controls" data-list-controls>
        @cmsHook('jobic.admin.taxonomies.list.filters.before', ['type' => $type])
        <label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('taxonomies.search') }}</span><input type="search" data-search placeholder="{{ __('taxonomies.search') }}" maxlength="100"></label>
        <div class="crud-filter-controls">@cmsHook('jobic.admin.taxonomies.list.filters.actions.before', ['type' => $type])<button type="button" class="vuero-button" data-show-selected hidden></button><button type="button" class="vuero-button" data-open-filters><x-vuero.icon name="filter" size="18" />{{ __('taxonomies.filters') }}</button>@cmsHook('jobic.admin.taxonomies.list.filters.actions.after', ['type' => $type])</div>
        @cmsHook('jobic.admin.taxonomies.list.filters.after', ['type' => $type])
    </div>
    @cmsHook('jobic.admin.taxonomies.list.table.before', ['type' => $type])
    <div class="crud-table-card" data-table-card>
        <div class="crud-loading" data-table-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('taxonomies.loading') }}</strong></div>
        @php($defaultColumns = $builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty() ? ['media','name','ordering','status','created_at','translations'] : ['name','ordering','status','created_at','translations'])
        <div class="crud-table-scroll"><table id="taxonomy-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="{{ __('taxonomies.select_all') }}"></th>@foreach(app(\App\Support\Hooks::class)->filter('jobic.taxonomies.list.columns', $defaultColumns, ['type'=>$type]) as $column)<th data-column="{{ $column }}">{{ __('taxonomies.'.$column) }}</th>@endforeach<th>{{ __('taxonomies.actions') }}</th></tr></thead><tbody></tbody></table></div>
        @include('common.crud.empty', ['title' => __('taxonomies.empty'), 'hint' => __('taxonomies.empty_hint')])
        <div data-table-error hidden role="alert"><span>{{ __('taxonomies.failed') }}</span><button type="button" data-retry class="vuero-button">{{ __('taxonomies.retry') }}</button></div>
    </div>
    <div data-table-footer data-list-controls></div>
    @cmsHook('jobic.admin.taxonomies.list.table.after', ['type' => $type])
    @cmsHook('jobic.admin.taxonomies.list.page.after', ['type' => $type])
</section>
<dialog class="crud-filter-dialog" data-filter-dialog><header><h2>{{ __('taxonomies.filters') }}</h2><button class="crud-icon-button" type="button" data-close-filters><x-vuero.icon name="x" /></button></header><form data-filter-form class="crud-filter-form"><div class="crud-filter-fields">@cmsHook('jobic.admin.taxonomies.list.filter_fields.before', ['type'=>$type])<label>{{ __('taxonomies.status') }}<select name="status"><option value="">{{ __('taxonomies.all') }}</option><option value="1">{{ __('taxonomies.active') }}</option><option value="0">{{ __('taxonomies.inactive') }}</option></select></label>@cmsHook('jobic.admin.taxonomies.list.filter_fields.after', ['type'=>$type])</div><footer><button class="vuero-button is-primary" type="submit">{{ __('taxonomies.apply') }}</button><button class="crud-icon-button" type="reset"><x-vuero.icon name="rotate" /></button></footer></form></dialog>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/taxonomies/index.js', 'vuero/build') @endpush
