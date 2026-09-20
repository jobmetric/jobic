@extends('common.admin')
@section('title', __('users.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create'))))
@section('content')
<form class="crud-page crud-user-form" data-user-form method="POST" action="{{ $record ? route('admin.users.update', $record->id) : route('admin.users.store') }}" @if($readonly) data-readonly @endif>
    @if($record)<input type="hidden" name="_version" value="{{ \App\Support\RecordVersion::token($record, 'users') }}">@endif
    @csrf @if($record) @method('PUT') @endif
    <header class="crud-toolbar is-sticky"><div><h1>{{ __('users.'.($readonly ? 'view_title' : ($record ? 'edit_title' : 'create'))) }}</h1></div><div data-crud-toolbar></div></header>
    @unless($readonly)<p class="crud-hint">{{ __('users.save_hint') }}</p>@endunless
    @cmsHook('jobic.admin.users.form.before', ['id' => $record?->id])
    <fieldset class="crud-form-fields" @disabled($readonly)>
        <x-form.field variant="crud" :label="__('users.name')" for="user-name" error="name"><x-form.input id="user-name" name="name" :value="old('name', $record?->name)" maxlength="100" required /></x-form.field>
        <x-form.field variant="crud" :label="__('users.email')" for="user-email" error="email"><x-form.input id="user-email" name="email" type="email" :value="old('email', $record?->email)" maxlength="255" dir="ltr" required /></x-form.field>
        @unless($readonly)
        @foreach(['password' => 'user-password', 'password_confirmation' => 'user-password-confirmation'] as $field => $id)
            <x-form.field variant="crud" :label="__('users.'.$field)" for="{{ $id }}" error="{{ $field }}"><div class="cms-settings-control"><x-form.password id="{{ $id }}" :name="$field" minlength="12" autocomplete="new-password" :required="!$record" aria-describedby="user-password-guidance @if($field === 'password') user-password-strength @endif" :meter="$field === 'password'" /></div></x-form.field>
        @endforeach
        <p id="user-password-guidance" class="cms-password-guidance"><x-vuero.icon name="info" size="15" />{{ __('admin.password_guidance') }}</p>
        @endunless
        <x-form.field variant="crud" :label="__('users.status')" for="user-status" error="status"><x-form.select id="user-status" name="status"><option value="1" @selected((string)old('status', $record?->status ?? 1) === '1')>{{ __('users.active') }}</option><option value="0" @selected((string)old('status', $record?->status ?? 1) === '0')>{{ __('users.inactive') }}</option></x-form.select></x-form.field>
    </fieldset>
    @cmsHook('jobic.admin.users.form.after', ['id' => $record?->id])
</form>
@include('common.crud.dialog')
@cmsHook('jobic.admin.users.history.before', ['id' => $record?->id])
<section class="crud-history crud-page" data-record-history @if(!$record) hidden @endif>
    <header><h2>{{ __('users.history') }}</h2><button type="button" class="vuero-button" data-history-refresh>{{ __('users.history_refresh') }}</button></header>
    <p class="crud-hint">{{ __('users.history_hint') }}</p><div data-history-entries aria-live="polite"></div>
    <footer><button type="button" class="vuero-button" data-history-previous>{{ __('users.previous') }}</button><span data-history-page></span><button type="button" class="vuero-button" data-history-next>{{ __('users.next') }}</button></footer>
</section>
@cmsHook('jobic.admin.users.history.after', ['id' => $record?->id])
@endsection
@push('scripts') @vite('resources/js/users/form.js', 'vuero/build') @endpush
