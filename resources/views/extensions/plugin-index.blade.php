@extends('common.admin')
@section('title', __('extensions.plugins'))
@section('content')
<section class="crud-page" data-extension-list>
    <header class="crud-toolbar"><div class="crud-taxonomy-heading">@if(app(\App\Support\AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.view'))<a class="crud-title-back" href="{{ $back_url }}" aria-label="{{ __('extensions.back_extensions') }}"><x-vuero.icon name="arrow-left" size="24" /></a>@endif<div><h1>{{ __('extensions.plugins') }}</h1></div></div><div data-crud-toolbar data-list-controls></div></header>
    <p class="crud-hint" data-extension-paused @if($extension_active) hidden @endif>{{ __('extensions.instances_paused') }}</p>
    @cmsHook('jobic.admin.extensions.list.before')
    <div class="crud-stats">@foreach(['total' => 'apps', 'active' => 'check-circle', 'inactive' => 'clock', 'recent' => 'calendar'] as $stat => $statIcon)<article><span>{{ __('extensions.'.$stat) }}</span><strong data-extension-stat>—</strong><x-vuero.icon :name="$statIcon" size="32" /></article>@endforeach</div>
    <div class="crud-list-controls" data-list-controls>
        <label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('extensions.search') }}</span><input type="search" data-search placeholder="{{ __('extensions.search') }}" maxlength="100"></label>
        <div class="crud-filter-controls"><button type="button" class="vuero-button" data-show-selected hidden></button><button type="button" class="vuero-button" data-open-filters><x-vuero.icon name="filter" size="18" />{{ __('extensions.filters') }}</button></div>
    </div>
    <div class="crud-table-card" data-table-card>
        <div class="crud-loading" data-table-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('extensions.loading') }}</strong></div>
        <div class="crud-table-scroll"><table id="extension-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="{{ __('extensions.select_all') }}"></th>@foreach(['name','status','created_at','updated_at','actions'] as $column)<th>{{ __('extensions.'.$column) }}</th>@endforeach</tr></thead><tbody></tbody></table></div>
        @include('common.crud.empty', ['title' => __('extensions.empty'), 'hint' => __('extensions.empty_hint')])
        <div data-table-error hidden role="alert"><span>{{ __('extensions.failed') }}</span><button type="button" data-retry class="vuero-button">{{ __('extensions.retry') }}</button></div>
    </div>
    <div data-table-footer data-list-controls></div>
    @cmsHook('jobic.admin.extensions.list.after')
</section>
<dialog class="crud-filter-dialog" data-filter-dialog aria-labelledby="extension-filters-title">
    <header><div><span>{{ __('extensions.plugins') }}</span><h2 id="extension-filters-title">{{ __('extensions.filters') }}</h2></div><button class="crud-icon-button" type="button" data-close-filters aria-label="{{ __('extensions.dismiss') }}"><x-vuero.icon name="x" /></button></header>
    <form data-filter-form class="crud-filter-form"><div class="crud-filter-fields">
        <label>{{ __('extensions.status') }}<select name="status"><option value="">{{ __('extensions.all') }}</option>@foreach(['active','inactive'] as $status)<option value="{{ $status }}">{{ __('extensions.'.$status) }}</option>@endforeach</select></label>
        @foreach(['created' => ['recent','older'], 'updated' => ['recent','older']] as $key => $options)
        <label>{{ __('extensions.filter_'.$key) }}<select name="{{ $key }}"><option value="">{{ __('extensions.all') }}</option>@foreach($options as $option)<option value="{{ $option }}">{{ __('extensions.filter_'.$key.'_'.$option) }}</option>@endforeach</select></label>
        @endforeach
    </div><footer><button class="vuero-button is-primary" type="submit">{{ __('extensions.apply') }}</button><button class="crud-icon-button" type="reset" aria-label="{{ __('extensions.reset') }}" data-tooltip="{{ __('extensions.reset') }}"><x-vuero.icon name="rotate" /></button></footer></form>
</dialog>
@include('common.crud.dialog')

@endsection
@push('scripts') @vite('resources/js/extensions/index.js', 'vuero/build') @endpush
