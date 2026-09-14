@extends('common.admin', ['script' => 'resources/js/installation/wizard.js'])
@section('title', __('installation.mail_settings'))
@section('content')
    <main class="cms-admin installation settings-mail">
        <header class="cms-admin__header"><h1>{{ __('installation.mail_settings') }}</h1></header>
        @if (session('status'))<p role="status">{{ session('status') }}</p>@endif
        @if ($errors->any())<div role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.settings.mail.update') }}" class="installation-panel">
            @csrf @method('PUT')
            <p>{{ __('installation.mail_hint') }}</p>
            <p>{{ __('installation.secret_hint') }}</p>
            <x-installation.fields :fields="$fields" :values="$values" />
            <button type="submit" class="button button--primary">{{ __('installation.save') }}</button>
        </form>
    </main>
@endsection
