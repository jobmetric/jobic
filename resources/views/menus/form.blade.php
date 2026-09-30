@extends('common.admin')
@section('title', __('menus.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create_title'))))
@section('content')
<form class="crud-page" data-menu-form method="POST" action="{{ $record ? route('admin.menus.update', $record->id) : route('admin.menus.store') }}" @if($readonly) data-readonly @endif>
    @if($record)<input type="hidden" name="_version" value="{{ \App\Support\RecordVersion::token($record, 'menus') }}">@endif
    @csrf @if($record) @method('PUT') @endif
    <header class="crud-toolbar is-sticky"><div><h1>{{ __('menus.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create_title'))) }}</h1></div><div data-crud-toolbar></div></header>
    @unless($readonly)<p class="crud-hint">{{ __('menus.save_hint') }}</p>@endunless
    @cmsHook('jobic.admin.menus.form.before', ['id' => $record?->id])
    <fieldset class="crud-form-fields" @disabled($readonly)>
        <x-form.field variant="crud" :label="__('menus.name')" for="menu-name" error="name"><x-form.input id="menu-name" name="name" :value="old('name', $record?->getTranslation('name'))" maxlength="150" required /></x-form.field>
        <x-form.field variant="crud" :label="__('menus.ordering')" for="menu-ordering" error="ordering"><x-form.input id="menu-ordering" name="ordering" type="number" :value="old('ordering', $record?->ordering ?? 0)" min="0" max="1000000" required /></x-form.field>
        <x-form.field variant="crud" :label="__('menus.status')" for="menu-status" error="status"><x-form.select id="menu-status" name="status"><option value="1" @selected((string)old('status', $record?->status ?? 1) === '1')>{{ __('menus.active') }}</option><option value="0" @selected((string)old('status', $record?->status ?? 1) === '0')>{{ __('menus.inactive') }}</option></x-form.select></x-form.field>
    </fieldset>
    @cmsHook('jobic.admin.menus.form.after', ['id' => $record?->id])
</form>
@include('common.crud.dialog')
@endsection
@push('scripts') @vite('resources/js/menus/form.js', 'vuero/build') @endpush
