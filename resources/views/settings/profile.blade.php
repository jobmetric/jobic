@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.profile.update') }}" class="cms-settings-form">@csrf @method('PUT')
        <div class="installation-fields">
            <label class="installation-field"><span>{{ __('admin.fields.name') }}</span><input name="name" type="text" value="{{ old('name', $account->name) }}" required autocomplete="name" /></label>
            <label class="installation-field"><span>{{ __('admin.fields.email') }}</span><input name="email" type="email" value="{{ old('email', $account->email) }}" required autocomplete="email" /></label>
        </div><button class="vuero-button is-primary" type="submit">{{ __('admin.save') }}</button>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'profile'])
    </form>
@endsection
