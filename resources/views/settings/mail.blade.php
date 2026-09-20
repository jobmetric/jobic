@extends('common.settings.page', ['section' => 'mail'])
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.mail.update') }}" class="cms-settings-form">
        @csrf @method('PUT')
        <section class="cms-settings-section">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="mail" size="21" /></span><div><h3>{{ __('system.mail_configuration') }}</h3><p>{{ __('installation.mail_hint') }}</p></div></header>
            <x-settings.fields :fields="$fields" :values="$values" />
            <div class="cms-settings-notice"><x-vuero.icon name="shield" size="18" /><span>{{ __('installation.secret_hint') }}</span></div>
            <div class="cms-mail-deliverability"><x-vuero.icon name="mail" size="19" /><div><strong>{{ __('system.deliverability_title') }}</strong><p>{{ __('system.deliverability_hint') }}</p></div></div>
        </section>
        <section class="cms-settings-section cms-settings-test-panel">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="activity" size="21" /></span><div><h3>{{ __('system.mail_diagnostics') }}</h3><p>{{ __('system.mail_diagnostics_hint') }}</p></div></header>
            <div class="cms-settings-test-row">
                <x-form.field :label="__('system.recipient')" for="recipient"><x-form.input id="recipient" name="recipient" type="email" placeholder="name@example.com" /></x-form.field>
                <div class="cms-settings-test-actions"><button type="submit" formnovalidate data-test-action="{{ route('admin.settings.mail.test') }}" value="connection" class="vuero-button">{{ __('installation.test') }}</button><button type="submit" formnovalidate data-test-action="{{ route('admin.settings.mail.test') }}" value="send" class="vuero-button">{{ __('system.send_test') }}</button></div>
            </div>
            <p class="cms-settings-test-result" role="status" aria-live="polite" data-test-result></p>
        </section>
        <div class="cms-settings-form__footer"><p>{{ __('system.lifecycle') }}</p><button type="submit" class="vuero-button is-primary"><x-vuero.icon name="check" size="16" />{{ __('installation.save') }}</button></div>
        @cmsHook('jobic.admin.settings.form.end', ['section' => 'mail'])
    </form>
@endsection
