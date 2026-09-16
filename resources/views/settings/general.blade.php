@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.general.update') }}" class="installation cms-settings-form">@csrf @method('PUT')
        <x-installation.fields :fields="['APP_NAME' => ['text', ''], 'APP_URL' => ['url', '']]" :values="$site" />
        <button class="vuero-button is-primary" type="submit">{{ __('admin.save') }}</button>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'general'])
    </form>
@endsection
