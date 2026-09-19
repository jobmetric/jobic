@extends('common.settings.page', ['section' => 'cache'])
@section('settings-content')
<form method="POST" action="{{ route('admin.settings.cache.update') }}" class="cms-settings-form">
    @csrf @method('PUT')
    <section class="cms-settings-section">
        <header class="cms-settings-section__header"><span><x-vuero.icon name="server" size="21" /></span><div><h3>{{ __('system.cache_configuration') }}</h3><p>{{ __('system.cache_hint') }}</p></div></header>
        <x-settings.fields :fields="$fields" :values="$values" />
        <div class="cms-settings-notice"><x-vuero.icon name="shield" size="18" /><span>{{ __('installation.secret_hint') }}</span></div>
    </section>
    <section class="cms-settings-section cms-settings-test-panel">
        <header class="cms-settings-section__header"><span><x-vuero.icon name="activity" size="21" /></span><div><h3>{{ __('system.cache_diagnostics') }}</h3><p>{{ __('system.cache_diagnostics_hint') }}</p></div></header>
        <div class="cms-settings-test-actions"><button type="submit" formnovalidate data-test-action="{{ route('admin.settings.cache.test') }}" value="connection" class="vuero-button"><x-vuero.icon name="activity" size="16" />{{ __('installation.test') }}</button></div>
        <p class="cms-settings-test-result" role="status" aria-live="polite" data-test-result></p>
    </section>
    <div class="cms-settings-form__footer"><p>{{ __('system.lifecycle') }}</p><button type="submit" class="vuero-button is-primary"><x-vuero.icon name="check" size="16" />{{ __('admin.save') }}</button></div>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'cache'])
</form>
@endsection
