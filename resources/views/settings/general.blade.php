@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.general.update') }}" class="cms-settings-form">@csrf @method('PUT')
        <section class="cms-settings-section">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="building" size="20" /></span><div><h3>{{ __('admin.website_identity') }}</h3><p>{{ __('admin.website_identity_hint') }}</p></div></header>
            <x-settings.fields :fields="['APP_NAME' => ['text', ''], 'APP_URL' => ['url', '']]" :values="$site" />
        </section>
        <section class="cms-settings-section">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="layout" size="20" /></span><div><h3>{{ __('layouts.active_theme') }}</h3><p>{{ __('layouts.active_theme_hint') }}</p></div></header>
            <div class="cms-settings-fields">
                <x-form.field data-field="active_theme" :label="__('layouts.active_theme')" for="active_theme">
                    <x-form.select id="active_theme" name="active_theme">
                        @foreach($siteThemes as $themeKey => $theme)
                            <option value="{{ $themeKey }}" @selected(old('active_theme', $site['active_theme']) === $themeKey)>{{ $theme->info['title'] ?? $theme->name }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>
        </section>
        <section class="cms-settings-section cms-timezone-section">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="clock" size="20" /></span><div><h3>{{ __('admin.server_timezone') }}</h3><p>{{ __('admin.server_timezone_hint') }}</p></div></header>
            <div class="cms-timezone-layout">
                <x-form.field data-field="APP_TIMEZONE" :label="__('admin.timezone')" for="APP_TIMEZONE" hint="{{ __('admin.timezone_field_hint') }}"><x-form.select id="APP_TIMEZONE" name="APP_TIMEZONE">@foreach($timezones as $timezone)<option value="{{ $timezone }}" @selected(old('APP_TIMEZONE', $site['APP_TIMEZONE']) === $timezone)>{{ $timezone }}</option>@endforeach</x-form.select></x-form.field>
                <aside class="cms-timezone-clock"><span><x-vuero.icon name="clock" size="18" /></span><div><small>{{ __('admin.current_server_time') }}</small><strong dir="ltr">{{ $serverTime }}</strong></div></aside>
            </div>
            <div class="cms-timezone-recommendation"><span><x-vuero.icon name="check-circle" size="19" /></span><div><strong>{{ __('admin.utc_recommended') }}</strong><p>{{ __('admin.utc_recommended_hint') }}</p></div></div>
        </section>
        <footer class="cms-settings-form__footer"><p>{{ __('admin.website_save_hint') }}</p><button class="vuero-button is-primary" type="submit"><x-vuero.icon name="check" size="16" />{{ __('admin.save') }}</button></footer>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'general'])
    </form>
@endsection
