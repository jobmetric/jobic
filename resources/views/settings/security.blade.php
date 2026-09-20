@extends('common.settings.page')
@section('settings-content')
    <form method="POST" action="{{ route('admin.settings.security.update') }}" class="cms-settings-form cms-security-form">@csrf @method('PUT')
        <section class="cms-settings-section">
            <header class="cms-settings-section__header"><span><x-vuero.icon name="lock" size="20" /></span><div><h3>{{ __('admin.password_title') }}</h3><p>{{ __('admin.password_intro') }}</p></div></header>
            <div class="cms-settings-fields">
                @foreach(['password' => 'new-password', 'password_confirmation' => 'confirm-password'] as $field => $id)
                    <x-form.field :label="__('admin.fields.'.$field)" for="{{ $id }}"><x-form.password id="{{ $id }}" :name="$field" minlength="12" required autocomplete="new-password" aria-describedby="password-guidance @if($field === 'password') password-strength @endif" :meter="$field === 'password'" /></x-form.field>
                @endforeach
            </div>
        </section>
        <footer class="cms-settings-form__footer"><p id="password-guidance"><x-vuero.icon name="info" size="15" />{{ __('admin.password_guidance') }}</p><button class="vuero-button is-primary" type="submit"><x-vuero.icon name="check" size="16" />{{ __('admin.save_password') }}</button></footer>
    @cmsHook('jobic.admin.settings.form.end', ['section' => 'security'])
    </form>
    <form method="POST" action="{{ route('admin.settings.security.two-factor') }}" class="cms-settings-form cms-security-form" data-two-factor-form>
        @csrf
        <div data-two-factor-content>@include('settings.partials.two-factor', ['state' => session('two_factor_state', ['enabled' => (bool) $account->two_factor_enabled_at])])</div>
    </form>
    <section class="cms-settings-form cms-security-form cms-security-activity">
        <header class="cms-settings-section__header"><span><x-vuero.icon name="activity" size="20" /></span><div><h3>{{ __('admin.recent_activity') }}</h3></div></header>
        @if($loginEvents->isNotEmpty())
            <section class="cms-health-table cms-security-activity__table" aria-label="{{ __('admin.recent_activity') }}">
                <header><span>{{ __('system.date') }}</span><span>{{ __('system.ip_address') }}</span><span>{{ __('system.status') }}</span><span>{{ __('system.result') }}</span></header>
                @foreach($loginEvents as $event)
                    <article class="cms-health-table__row">
                        <div class="cms-health-table__name"><span><x-vuero.icon name="clock" size="18" /></span><strong dir="ltr">{{ $event->created_at->format('Y-m-d H:i') }}</strong></div>
                        <p dir="ltr">{{ $event->ip_address ?: '—' }}</p>
                        <div><span class="cms-health-status {{ $event->successful ? 'is-ready' : 'is-unavailable' }}">{{ __('admin.'.($event->successful ? 'successful' : 'failed')) }}</span></div>
                        <p>{{ $event->successful ? __('system.login_allowed') : __('system.login_denied') }}</p>
                    </article>
                @endforeach
            </section>
        @else
            <p class="cms-settings-empty">{{ __('admin.no_activity') }}</p>
        @endif
    </section>
@endsection
