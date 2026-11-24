@extends('common.admin')
@section('title', __('layouts.title'))
@section('content')
<section class="crud-page" data-layout-list>
    <header class="crud-toolbar"><div><h1>{{ __('layouts.title') }}</h1><p>{{ __('layouts.intro') }}</p></div><div data-crud-toolbar data-list-controls></div></header>
    @cmsHook('jobic.admin.layouts.list.before')
    <div class="crud-stats">@foreach(['total'=>'layout','active'=>'check-circle','inactive'=>'pause-circle','deleted_count'=>'trash'] as $stat=>$icon)<article><span>{{ __('layouts.'.$stat) }}</span><strong data-layout-stat>—</strong><x-vuero.icon :name="$icon" size="32" /></article>@endforeach</div>
    <nav class="extension-quick-filters" data-quick-filters aria-label="{{ __('layouts.quick_filters') }}">@foreach(['all','active','inactive','deleted'] as $filter)<button type="button" data-quick-filter="{{ $filter }}" aria-pressed="{{ $filter === 'all' ? 'true' : 'false' }}"><span>{{ __('layouts.'.$filter) }}</span><small data-quick-count="{{ $filter }}">0</small></button>@endforeach</nav>
    <div class="crud-list-controls" data-list-controls><label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('layouts.search') }}</span><input type="search" data-search placeholder="{{ __('layouts.search') }}" maxlength="100"></label><div class="crud-filter-controls"><button type="button" class="vuero-button" data-show-selected hidden></button><button type="button" class="vuero-button" data-open-filters><x-vuero.icon name="filter" size="18" />{{ __('layouts.filters') }}</button></div></div>
    <div class="crud-table-card" data-table-card>
        <div class="crud-loading" data-table-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('layouts.loading') }}</strong></div>
        <div class="crud-table-scroll"><table id="layout-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="{{ __('layouts.select_all') }}"></th>@foreach(['name','theme','page_types_label','modules','status','actions'] as $column)<th>{{ __('layouts.'.$column) }}</th>@endforeach</tr></thead><tbody></tbody></table></div>
        @include('common.crud.empty', ['title'=>__('layouts.empty'),'hint'=>count($themes) ? __('layouts.empty_hint') : __('layouts.theme_required_hint'),'actionUrl'=>count($themes) ? route('admin.settings.layouts.create') : route('admin.settings.extensions.index'),'actionLabel'=>count($themes) ? __('layouts.create') : __('layouts.manage_themes'),'actionHidden'=>count($themes) ? !app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.layouts.manage') : !app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.view'),'clearLabel'=>__('layouts.clear_filters')])
        <div data-table-error hidden role="alert"><span>{{ __('layouts.failed') }}</span><button type="button" data-retry class="vuero-button">{{ __('layouts.retry') }}</button></div>
    </div>
    <div data-table-footer data-list-controls></div>
    @cmsHook('jobic.admin.layouts.list.after')
</section>
<dialog class="crud-filter-dialog" data-filter-dialog aria-labelledby="layout-filters-title"><header><div><span>{{ __('layouts.title') }}</span><h2 id="layout-filters-title">{{ __('layouts.filters') }}</h2></div><button class="crud-icon-button" type="button" data-close-filters aria-label="{{ __('layouts.dismiss') }}"><x-vuero.icon name="x" /></button></header><form data-filter-form class="crud-filter-form"><div class="crud-filter-fields"><label>{{ __('layouts.theme') }}<select name="theme"><option value="">{{ __('layouts.all') }}</option>@foreach($themes as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label><label>{{ __('layouts.status') }}<select name="status"><option value="">{{ __('layouts.all') }}</option>@foreach(['active','inactive','deleted'] as $status)<option value="{{ $status }}">{{ __('layouts.'.$status) }}</option>@endforeach</select></label></div><footer><button class="vuero-button is-primary" type="submit">{{ __('layouts.apply') }}</button><button class="crud-icon-button" type="reset" aria-label="{{ __('layouts.reset') }}" data-tooltip="{{ __('layouts.reset') }}"><x-vuero.icon name="rotate" /></button></footer></form></dialog>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/layouts/index.js', 'vuero/build') @endpush
@push('styles') @vite('resources/css/layouts.css', 'vuero/build') @endpush
