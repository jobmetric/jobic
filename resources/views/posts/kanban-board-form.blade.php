@extends('common.admin')
@section('title', __('kanban.'.($record ? 'edit' : 'create_board')))
@section('content')
<form class="crud-page crud-role-form" data-board-form method="POST" action="{{ $record ? route('admin.posts.kanban.boards.update', ['type'=>$type,'board'=>$record->id]) : route('admin.posts.kanban.boards.store', ['type'=>$type]) }}" novalidate>
    @csrf
    @if($record) @method('PUT') @endif
    <header class="crud-toolbar is-sticky"><div><h1 data-board-form-title>{{ __('kanban.'.($record ? 'edit' : 'create_board')) }}</h1></div><div data-crud-toolbar></div></header>
    <p class="crud-hint">{{ __('kanban.save_hint') }}</p>
    @cmsHook('jobic.admin.posts.kanban.boards.form.before', ['type'=>$type,'id'=>$record?->id])
    <nav class="role-form-tabs" role="tablist" aria-label="{{ __('kanban.form_sections') }}">
        @foreach(['details_tab','columns_tab','filters_tab'] as $index=>$tab)<button type="button" role="tab" aria-selected="{{ $index===0 ? 'true' : 'false' }}" aria-controls="board-panel-{{ $index }}" id="board-tab-{{ $index }}" data-board-tab="{{ $index }}">{{ __('kanban.'.$tab) }}</button>@endforeach
    </nav>
    <section id="board-panel-0" role="tabpanel" aria-labelledby="board-tab-0" data-board-panel="0"><fieldset class="crud-form-fields board-details-fields">
        <x-form.field variant="crud" class="board-name-field" :label="__('kanban.name')" for="board-name" error="name"><x-form.input name="name" id="board-name" :value="$record?->name" maxlength="160" required /></x-form.field>

        <x-form.field variant="crud" :label="__('kanban.roles')" for="board-roles" error="roles"><x-form.select name="roles[]" id="board-roles" multiple>@foreach($choices['roles'] as $choice)<option value="{{ $choice['id'] }}" @selected(in_array($choice['id'], $record?->roles ?? []))>{{ $choice['name'] }}</option>@endforeach</x-form.select></x-form.field>
        <x-form.field variant="crud" :label="__('kanban.users')" for="board-users" error="users"><x-form.select name="users[]" id="board-users" multiple>@foreach($choices['users'] as $choice)<option value="{{ $choice['id'] }}" @selected(in_array($choice['id'], $record?->users ?? []))>{{ $choice['name'] }}</option>@endforeach</x-form.select></x-form.field>
    </fieldset><p class="crud-hint">{{ __('kanban.audience_hint') }}</p></section>
    <section id="board-panel-1" role="tabpanel" aria-labelledby="board-tab-1" data-board-panel="1" hidden>
        <p class="crud-hint">{{ __('kanban.columns_hint') }}</p>
        <div class="board-column-picker">
            <section class="board-column-pool"><header><h2>{{ __('kanban.available_columns') }}</h2><span class="board-count" data-board-available-count></span></header><div data-board-state-available class="board-column-list"></div></section>
            <section class="board-column-pool"><header><h2>{{ __('kanban.selected_columns') }}</h2><span class="board-count" data-board-selected-count></span></header><div id="board-states" data-board-state-order class="board-column-list" tabindex="-1" role="group" aria-label="{{ __('kanban.selected_columns') }}" aria-describedby="error-states"></div><small data-error-for="states" id="error-states" role="alert"></small></section>
        </div>
    </section>
    <section id="board-panel-2" role="tabpanel" aria-labelledby="board-tab-2" data-board-panel="2" hidden>
        <div class="role-rule-toolbar"><div><h2>{{ __('kanban.filters') }}</h2><p class="crud-hint">{{ __('kanban.filters_hint') }}</p></div><button class="vuero-button is-primary" type="button" data-board-add-filter><x-vuero.icon name="plus" size="18" />{{ __('kanban.add_filter') }}</button></div>
        <div data-board-filters></div><small data-error-for="filters" id="error-filters"></small>
    </section>
    @cmsHook('jobic.admin.posts.kanban.boards.form.after', ['type'=>$type,'id'=>$record?->id])
</form>
@include('common.crud.dialog')
<x-history.disclosure>
<section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
    <header><h2>{{ __('kanban.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('languages.history_refresh') }}</button></header>
    <div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('kanban.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('kanban.next') }}</button></footer>
</section>
</x-history.disclosure>
@endsection
@push('scripts') @vite('resources/js/kanban/board-form.js', 'vuero/build') @endpush
