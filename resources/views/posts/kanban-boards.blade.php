@extends('common.admin')
@section('title', __('kanban.boards'))
@section('content')
<section class="crud-page" data-board-directory>
    @cmsHook('jobic.admin.posts.kanban.boards.before', ['type' => $type])
    <div data-board-list>
        <header class="crud-toolbar"><div class="crud-taxonomy-heading"><a class="crud-title-back" href="{{ route('admin.posts.index', ['type' => $type]) }}" aria-label="{{ __('kanban.back') }}"><x-vuero.icon name="arrow-left" size="24" /></a><div><h1>{{ __('kanban.boards') }}</h1></div></div><div data-crud-toolbar data-list-controls>
            @if(app(\App\Services\Posts\ContentBoards::class)->mayManage($type, auth()->user()))<a class="vuero-button is-primary" data-board-create href="{{ route('admin.posts.kanban.boards.create', ['type' => $type]) }}"><x-vuero.icon name="plus" size="18" />{{ __('kanban.create_board') }}</a>@endif
            <a class="vuero-button" data-board-default href="{{ route('admin.posts.kanban.default', ['type' => $type]) }}"><x-vuero.icon name="kanban" size="18" />{{ __('kanban.default_board') }}</a>
            <details class="crud-dropdown"><summary class="vuero-button">{{ __('kanban.actions') }}<x-vuero.icon name="chevron-down" size="18" /></summary><div class="crud-dropdown__menu"><button type="button" data-board-bulk hidden>{{ __('kanban.delete') }}</button><button type="button" data-board-help>{{ __('kanban.help') }}</button></div></details>
        </div></header>
        <div class="crud-stats">@foreach(['total_boards'=>'kanban','open_boards'=>'globe','restricted_boards'=>'shield','recent_boards'=>'calendar'] as $stat=>$statIcon)<article><span>{{ __('kanban.'.$stat) }}</span><strong data-board-stat>—</strong><x-vuero.icon :name="$statIcon" size="32" /></article>@endforeach</div>
        <div class="crud-list-controls"><label class="crud-search"><x-vuero.icon name="search" size="20" /><span class="sr-only">{{ __('kanban.search') }}</span><input type="search" data-board-search maxlength="160" placeholder="{{ __('kanban.search') }}"></label><div class="crud-filter-controls"><button type="button" class="vuero-button" data-board-selected hidden></button><button type="button" class="vuero-button" data-board-open-filters><x-vuero.icon name="filter" size="18" />{{ __('kanban.filters') }}<span data-board-filter-count hidden></span></button></div></div>
        <div class="crud-table-card" data-table-card>
            <div class="crud-loading" data-board-loading hidden role="status"><span class="crud-spinner"></span><strong>{{ __('kanban.loading') }}</strong></div>
            <div class="crud-table-scroll"><table id="board-table"><thead><tr><th><input type="checkbox" data-board-select-all aria-label="{{ __('kanban.select_all') }}"></th><th><span data-board-column-label>{{ __('kanban.name') }}</span></th><th><span data-board-column-label>{{ __('kanban.updated_at') }}</span></th><th><span data-board-column-label>{{ __('kanban.actions') }}</span></th></tr></thead><tbody data-board-rows></tbody></table></div>
            @include('common.crud.empty', ['title' => __('kanban.no_boards'), 'hint' => __('kanban.empty_boards_hint')])
            <div data-board-error role="alert" hidden></div><button type="button" class="vuero-button" data-board-retry hidden>{{ __('kanban.retry') }}</button>
        </div>
        <div data-board-footer></div>
    </div>
    @cmsHook('jobic.admin.posts.kanban.boards.after', ['type' => $type])
</section>
<dialog class="crud-filter-dialog" data-board-filter-dialog aria-labelledby="board-filters-title">
    <header><div><span>{{ __('kanban.boards') }}</span><h2 id="board-filters-title">{{ __('kanban.filters') }}</h2></div><button class="crud-icon-button" type="button" data-board-close-filters aria-label="{{ __('kanban.close') }}"><x-vuero.icon name="x" /></button></header>
    <form data-board-filter-form class="crud-filter-form"><div class="crud-filter-fields">
        <x-form.field variant="crud" :label="__('kanban.audience_tab')" for="board-filter-audience"><x-form.select name="audience" id="board-filter-audience"><option value="">{{ __('kanban.all') }}</option><option value="open">{{ __('kanban.open_boards') }}</option><option value="restricted">{{ __('kanban.restricted_boards') }}</option></x-form.select></x-form.field>
        <x-form.field variant="crud" :label="__('kanban.content_filters')" for="board-filter-has-filters"><x-form.select name="has_filters" id="board-filter-has-filters"><option value="">{{ __('kanban.all') }}</option><option value="1">{{ __('kanban.with_filters') }}</option><option value="0">{{ __('kanban.without_filters') }}</option></x-form.select></x-form.field>
        @foreach(['states','roles','users'] as $field)
            @if($field==='states' || $filterOptions['manage'])
                <x-form.field variant="crud" :label="__('kanban.'.$field)" for="board-filter-{{ $field }}"><x-form.select :name="$field.'[]'" id="board-filter-{{ $field }}" multiple>@foreach($filterOptions[$field] as $choice)<option value="{{ $choice['id'] }}">{{ $choice['name'] }}</option>@endforeach</x-form.select></x-form.field>
            @endif
        @endforeach
    </div><footer><button class="vuero-button is-primary" type="submit">{{ __('kanban.apply_filters') }}</button><button class="crud-icon-button" type="reset" aria-label="{{ __('kanban.reset_filters') }}" data-tooltip="{{ __('kanban.reset_filters') }}"><x-vuero.icon name="rotate" /></button></footer></form>
</dialog>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/kanban/boards.js', 'vuero/build') @endpush
