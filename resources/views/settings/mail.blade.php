@extends('common.settings.page', ['section' => 'mail', 'script' => 'resources/js/installation/wizard.js'])
@section('settings-content')
        <form method="POST" action="{{ route('admin.settings.mail.update') }}" class="cms-settings-form installation">
            @csrf @method('PUT')
            <p>{{ __('installation.mail_hint') }}</p>
            <p>{{ __('installation.secret_hint') }}</p>
            <x-installation.fields :fields="$fields" :values="$values" />
            <button type="submit" class="button button--primary">{{ __('installation.save') }}</button>
        @cmsHook('jobic.admin.settings.form.end', ['section' => 'mail'])
    </form>
@endsection
