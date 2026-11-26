@extends('common.account.page')
@section('account-content')
    <form method="POST" action="{{ route('admin.account.profile.update') }}" class="cms-settings-form" data-avatar-save-url="{{ route('admin.account.profile.avatar') }}">@csrf @method('PUT')
        <section class="cms-profile-summary">
            <button class="cms-profile-avatar-button" type="button" data-avatar-open aria-label="{{ __('admin.change_avatar') }}"><x-user-avatar :user="$account" class="cms-profile-summary__avatar" /><span><x-vuero.icon name="edit" size="14" /></span></button>
            <div><span class="cms-profile-summary__eyebrow">{{ __('admin.account') }}</span><strong>{{ trim(($profileMeta['first_name'] ?? '').' '.($profileMeta['last_name'] ?? '')) ?: $account->name }}</strong><small>{{ $account->email }}</small></div>
            <span class="cms-profile-summary__badge"><x-vuero.icon name="shield" size="15" /> {{ __('admin.profile_private') }}</span>
        </section>
        <input type="hidden" name="avatar_preset" value="{{ old('avatar_preset', $account->avatar_preset) }}" data-avatar-input>
        <div class="cms-settings-fields">
            <x-form.field :label="__('admin.fields.first_name')" for="profile-first-name"><x-form.input id="profile-first-name" name="first_name" type="text" :value="old('first_name', $profileMeta['first_name'])" autocomplete="given-name" /></x-form.field>
            <x-form.field :label="__('admin.fields.last_name')" for="profile-last-name"><x-form.input id="profile-last-name" name="last_name" type="text" :value="old('last_name', $profileMeta['last_name'])" autocomplete="family-name" /></x-form.field>
            <x-form.field :label="__('admin.fields.gender')" for="profile-gender"><x-form.select id="profile-gender" name="gender" placeholder>@foreach(['male', 'female', 'other', 'prefer_not_to_say'] as $gender)<option value="{{ $gender }}" @selected(old('gender', $profileMeta['gender']) === $gender)>{{ __('admin.options.'.$gender) }}</option>@endforeach</x-form.select></x-form.field>
            <x-form.field :label="__('admin.fields.email')" for="profile-email" hint="{{ __('admin.email_hint') }}"><x-form.input id="profile-email" name="email" type="email" :value="old('email', $account->email)" required autocomplete="email" dir="ltr" /></x-form.field>
        </div>
        <footer class="cms-settings-form__footer"><p>{{ __('admin.profile_save_hint') }}</p><button class="vuero-button is-primary" type="submit"><x-vuero.icon name="check" size="16" />{{ __('admin.save') }}</button></footer>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'profile'])
    </form>
@endsection
