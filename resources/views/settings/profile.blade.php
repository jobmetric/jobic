@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.profile.update') }}" class="cms-settings-form">@csrf @method('PUT')
        <section class="cms-profile-summary">
            <button class="cms-profile-avatar-button" type="button" data-avatar-open aria-label="{{ __('admin.change_avatar') }}"><x-user-avatar :user="$account" class="cms-profile-summary__avatar" /><span><x-vuero.icon name="edit" size="14" /></span></button>
            <div><span class="cms-profile-summary__eyebrow">{{ __('admin.account') }}</span><strong>{{ $account->name }}</strong><small>{{ $account->email }}</small></div>
            <span class="cms-profile-summary__badge"><x-vuero.icon name="shield" size="15" /> {{ __('admin.profile_private') }}</span>
        </section>
        <input type="hidden" name="avatar_preset" value="{{ old('avatar_preset', $account->avatar_preset) }}" data-avatar-input>
        <div class="cms-settings-fields">
            <x-form.field :label="__('admin.fields.name')" for="profile-name" hint="{{ __('admin.name_hint') }}"><x-form.input id="profile-name" name="name" type="text" :value="old('name', $account->name)" required autocomplete="name" /></x-form.field>
            <x-form.field :label="__('admin.fields.email')" for="profile-email" hint="{{ __('admin.email_hint') }}"><x-form.input id="profile-email" name="email" type="email" :value="old('email', $account->email)" required autocomplete="email" dir="ltr" /></x-form.field>
        </div>
        <footer class="cms-settings-form__footer"><p>{{ __('admin.profile_save_hint') }}</p><button class="vuero-button is-primary" type="submit"><x-vuero.icon name="check" size="16" />{{ __('admin.save') }}</button></footer>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'profile'])
    </form>
@endsection
