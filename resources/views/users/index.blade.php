@extends('common.admin')
@section('title', __('users.title'))
@section('content')
<section class="crud-page" data-user-list>
    <header class="crud-toolbar"><div><h1>{{ __('users.title') }}</h1><p>{{ __('users.intro') }}</p></div><div data-crud-toolbar data-list-controls></div></header>
    @cmsHook('jobic.admin.users.list.before')
    <div class="crud-stats">@foreach(['total', 'active_count', 'verified_count', 'recent'] as $stat)<article><span>{{ __('users.'.$stat) }}</span><strong data-user-stat>—</strong><x-vuero.icon name="users" size="32" /></article>@endforeach</div>
    <div class="crud-list-controls" data-list-controls>
        <label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('users.search') }}</span><input type="search" data-search placeholder="{{ __('users.search') }}" maxlength="100"></label>
        <div class="crud-filter-controls"><button type="button" class="vuero-button" data-show-selected hidden></button><button type="button" class="vuero-button" data-open-filters><x-vuero.icon name="filter" size="18" />{{ __('users.filters') }}</button></div>
    </div>
    <div class="crud-table-card" data-table-card>
        <div class="crud-loading" data-table-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('users.loading') }}</strong></div>
        <div class="crud-table-scroll"><table id="user-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="{{ __('users.select_all') }}"></th>@foreach(['name','email','status','verified','created_at','actions'] as $column)<th>{{ __('users.'.$column) }}</th>@endforeach</tr></thead><tbody></tbody></table></div>
        @include('common.crud.empty', ['title' => __('users.empty'), 'hint' => __('users.empty_hint')])
        <div data-table-error hidden role="alert"><span>{{ __('users.failed') }}</span><button type="button" data-retry class="vuero-button">{{ __('users.retry') }}</button></div>
    </div>
    <div data-table-footer data-list-controls></div>
    @cmsHook('jobic.admin.users.list.after')
</section>
<dialog class="crud-filter-dialog" data-filter-dialog aria-labelledby="user-filters-title">
    <header><div><span>{{ __('users.title') }}</span><h2 id="user-filters-title">{{ __('users.filters') }}</h2></div><button class="crud-icon-button" type="button" data-close-filters aria-label="{{ __('users.dismiss') }}"><x-vuero.icon name="x" /></button></header>
    <form data-filter-form class="crud-filter-form"><div class="crud-filter-fields">
        @foreach(['status' => ['1'=>'active','0'=>'inactive'], 'verified'=>['1'=>'verified_yes','0'=>'verified_no']] as $name=>$options)
        <label>{{ __('users.'.$name) }}<select name="{{ $name }}"><option value="">{{ __('users.all') }}</option>@foreach($options as $value=>$label)<option value="{{ $value }}">{{ __('users.'.$label) }}</option>@endforeach</select></label>
        @endforeach
    </div><footer><button class="vuero-button is-primary" type="submit">{{ __('users.apply') }}</button><button class="crud-icon-button" type="reset" aria-label="{{ __('users.reset') }}" data-tooltip="{{ __('users.reset') }}"><x-vuero.icon name="rotate" /></button></footer></form>
</dialog>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/users/index.js', 'vuero/build') @endpush
