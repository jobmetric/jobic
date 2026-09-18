@extends('common.admin')
@section('title', __('languages.title'))
@section('content')
<section class="crud-page" data-language-list>
    <header class="crud-toolbar"><div><h1>{{ __('languages.title') }}</h1><p>{{ __('languages.intro') }}</p></div><div data-crud-toolbar data-list-controls></div></header>
    @cmsHook('jobic.admin.languages.list.before')
    <div class="crud-stats">@foreach(['total', 'active', 'rtl', 'recent'] as $stat)<article><span>{{ __('languages.'.$stat) }}</span><strong data-language-stat>—</strong><x-vuero.icon name="globe" size="32" /></article>@endforeach</div>
    <div class="crud-list-controls" data-list-controls>
        <label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('languages.search') }}</span><input type="search" data-search placeholder="{{ __('languages.search') }}" maxlength="100"></label>
        <div class="crud-filter-controls"><button type="button" class="vuero-button" data-show-selected hidden></button><button type="button" class="vuero-button" data-open-filters><x-vuero.icon name="filter" size="18" />{{ __('languages.filters') }}</button></div>
    </div>
    <div class="crud-table-card" data-table-card>
        <div class="crud-loading" data-table-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('languages.loading') }}</strong></div>
        <div class="crud-table-scroll"><table id="language-table"><thead><tr><th><input type="checkbox" data-select-all aria-label="{{ __('languages.select_all') }}"></th>@foreach(['name','locale','direction','status','created_at','actions'] as $column)<th>{{ __('languages.'.$column) }}</th>@endforeach</tr></thead><tbody></tbody></table></div>
        @include('common.crud.empty', ['title' => __('languages.empty'), 'hint' => __('languages.empty_hint')])
        <div data-table-error hidden role="alert"><span>{{ __('languages.failed') }}</span><button type="button" data-retry class="vuero-button">{{ __('languages.retry') }}</button></div>
    </div>
    <div data-table-footer data-list-controls></div>
    @cmsHook('jobic.admin.languages.list.after')
</section>
<dialog class="crud-filter-dialog" data-filter-dialog aria-labelledby="language-filters-title">
    <header><div><span>{{ __('languages.title') }}</span><h2 id="language-filters-title">{{ __('languages.filters') }}</h2></div><button class="crud-icon-button" type="button" data-close-filters aria-label="{{ __('languages.dismiss') }}"><x-vuero.icon name="x" /></button></header>
    <form data-filter-form class="crud-filter-form"><div class="crud-filter-fields">
        @foreach(['status' => ['1'=>'active','0'=>'inactive'], 'direction'=>['ltr'=>'ltr','rtl'=>'rtl']] as $name=>$options)
        <label>{{ __('languages.'.$name) }}<select name="{{ $name }}"><option value="">{{ __('languages.all') }}</option>@foreach($options as $value=>$label)<option value="{{ $value }}">{{ __('languages.'.$label) }}</option>@endforeach</select></label>
        @endforeach
    </div><footer><button class="vuero-button is-primary" type="submit">{{ __('languages.apply') }}</button><button class="crud-icon-button" type="reset" aria-label="{{ __('languages.reset') }}" data-tooltip="{{ __('languages.reset') }}"><x-vuero.icon name="rotate" /></button></footer></form>
</dialog>
@include('common.crud.dialog')

@endsection
@push('scripts') @vite('resources/js/languages/index.js', 'vuero/build') @endpush
