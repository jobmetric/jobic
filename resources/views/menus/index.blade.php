@extends('common.admin')
@section('title', __('menus.title'))
@section('content')
<section class="crud-page" data-menu-list>
    <header class="crud-toolbar"><div><h1>{{ __('menus.title') }}</h1><p>{{ __('menus.intro') }}</p></div><div data-crud-toolbar data-list-controls></div></header>
    @cmsHook('jobic.admin.menus.list.before')
    <div class="crud-stats">@foreach(['total', 'active', 'inactive', 'recent'] as $stat)<article><span>{{ __('menus.'.$stat) }}</span><strong data-menu-stat>—</strong><x-vuero.icon name="menu" size="32" /></article>@endforeach</div>
    <div class="crud-list-controls" data-list-controls>
        <label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('menus.search') }}</span><input type="search" data-search placeholder="{{ __('menus.search') }}" maxlength="100"></label>
        <div class="crud-filter-controls"><button type="button" class="vuero-button" data-show-selected hidden></button><button type="button" class="vuero-button" data-open-filters><x-vuero.icon name="filter" size="18" />{{ __('menus.filters') }}</button></div>
    </div>
    <div class="crud-table-card" data-table-card>
        <div class="crud-loading" data-table-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('menus.loading') }}</strong></div>
        <div class="crud-table-scroll"><table id="menu-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="{{ __('menus.select_all') }}"></th>@foreach(['name','ordering','status','created_at','actions'] as $column)<th>{{ __('menus.'.$column) }}</th>@endforeach</tr></thead><tbody></tbody></table></div>
        @include('common.crud.empty', ['title' => __('menus.empty'), 'hint' => __('menus.empty_hint')])
        <div data-table-error hidden role="alert"><span>{{ __('menus.failed') }}</span><button type="button" data-retry class="vuero-button">{{ __('menus.retry') }}</button></div>
    </div>
    <div data-table-footer data-list-controls></div>
    @cmsHook('jobic.admin.menus.list.after')
</section>
<dialog class="crud-filter-dialog" data-filter-dialog aria-labelledby="menu-filters-title">
    <header><div><span>{{ __('menus.title') }}</span><h2 id="menu-filters-title">{{ __('menus.filters') }}</h2></div><button class="crud-icon-button" type="button" data-close-filters aria-label="{{ __('menus.dismiss') }}"><x-vuero.icon name="x" /></button></header>
    <form data-filter-form class="crud-filter-form"><div class="crud-filter-fields"><label>{{ __('menus.status') }}<select name="status"><option value="">{{ __('menus.all') }}</option><option value="1">{{ __('menus.active') }}</option><option value="0">{{ __('menus.inactive') }}</option></select></label></div><footer><button class="vuero-button is-primary" type="submit">{{ __('menus.apply') }}</button><button class="crud-icon-button" type="reset" aria-label="{{ __('menus.reset') }}" data-tooltip="{{ __('menus.reset') }}"><x-vuero.icon name="rotate" /></button></footer></form>
</dialog>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/menus/index.js', 'vuero/build') @endpush
