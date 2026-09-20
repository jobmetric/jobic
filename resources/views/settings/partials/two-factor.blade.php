@php($enabled = $state['enabled'] ?? false)
@php($setup = $state['setup'] ?? false)
<section class="cms-settings-section">
    <header class="cms-settings-section__header cms-two-factor-header"><span><x-vuero.icon name="shield" size="20" /></span><div><h3>{{ __('two-factor.title') }}</h3><p>{{ __('two-factor.intro') }}</p></div><p class="cms-two-factor-status {{ $enabled ? 'is-enabled' : 'is-disabled' }}" role="status"><span class="cms-two-factor-status__dot" aria-hidden="true"></span><span>{{ __('two-factor.'.($enabled ? 'enabled' : 'disabled')) }}</span></p></header>
    @if($setup)
        <div class="cms-two-factor-setup">
            <img src="{{ $state['qr'] }}" width="240" height="240" alt="{{ __('two-factor.qr') }}" />
            <div><p>{{ __('two-factor.scan') }}</p><p>{{ __('two-factor.manual') }}</p><code dir="ltr">{{ $state['secret'] }}</code><p>{{ __('two-factor.setup_expiry') }}</p></div>
        </div>
    @endif
    @if(!empty($state['recovery_codes']))
        <div class="cms-two-factor-recovery"><h4>{{ __('two-factor.recovery_title') }}</h4><p>{{ __('two-factor.recovery_hint') }}</p><ul dir="ltr">@foreach($state['recovery_codes'] as $code)<li><code>{{ $code }}</code></li>@endforeach</ul></div>
    @endif
    <input type="hidden" name="action" value="{{ $enabled ? 'disable' : ($setup ? 'enable' : 'setup') }}" />
    <div class="cms-settings-fields">
        <x-form.field :label="__('two-factor.password')" for="two-factor-password"><x-form.input id="two-factor-password" name="current_password" type="password" autocomplete="current-password" required /></x-form.field>
        @if($setup || $enabled)
            <x-form.field :label="__('two-factor.code')" for="two-factor-code" hint="{{ __('two-factor.'.($enabled ? 'disable_hint' : 'enable_hint')) }}"><x-form.input id="two-factor-code" name="code" type="text" dir="ltr" autocomplete="one-time-code" :inputmode="$enabled ? 'text' : 'numeric'" :maxlength="$enabled ? 16 : 6" required data-secret aria-describedby="two-factor-code-hint" /></x-form.field>
        @endif
    </div>
</section>
<footer class="cms-settings-form__footer"><p>{{ __('two-factor.private') }}</p><button class="vuero-button is-primary" type="submit">{{ __('two-factor.'.($enabled ? 'disable' : ($setup ? 'enable' : 'setup'))) }}</button></footer>
