@extends('common.auth', ['authLocale' => app()->getLocale(), 'script' => 'resources/js/vuero/pages/auth/login.js'])
@section('title', __('legal.' . $policy . '.title'))
@section('content')
<main class="auth-shell auth-shell--legal legal-shell" data-auth-shell>
    <section class="auth-panel auth-panel--legal">
        <div class="auth-panel__tools">
            <a href="{{ route('home') }}" aria-label="{{ __('auth.screen.home') }}"><x-brand compact /></a>
            <div class="login-panel-actions">
                <button class="icon-button" type="button" data-panel-open="languages" aria-expanded="false" aria-controls="languages-panel" aria-label="{{ __('admin.language') }}"><span class="country-flag country-flag--{{ app()->getLocale() === 'fa' ? 'ir' : 'us' }}" aria-hidden="true"></span></button>
                <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="{{ __('auth.screen.theme') }}" aria-pressed="false"><x-vuero.icon class="theme-toggle__sun" name="sun" /><x-vuero.icon class="theme-toggle__moon" name="moon" /></button>
            </div>
        </div>
        <article class="legal-card">
            <header><h1 id="legal-title">{{ __('legal.' . $policy . '.title') }}</h1><p>{{ __('legal.' . $policy . '.intro') }}</p></header>
            <div class="legal-scroll" tabindex="0" role="region" aria-labelledby="legal-title">
                @foreach (__('legal.' . $policy . '.sections') as $section)
                    <section><h2>{{ $section['title'] }}</h2><p>{{ strtr($section['text'], [':days' => (string) max(1, (int) config('auth.remember_days', 30)), ':minutes' => (string) config('session.lifetime')]) }}</p></section>
                @endforeach
            </div>
            <footer>
                <a class="legal-return" href="{{ route('login') }}"><x-vuero.icon name="arrow-left" size="18" />{{ __('legal.back') }}</a>
                <a class="legal-other" href="{{ route($policy === 'privacy' ? 'legal.cookies' : 'legal.privacy') }}">{{ __('legal.' . ($policy === 'privacy' ? 'cookies' : 'privacy') . '.title') }}<x-vuero.icon name="arrow-right" size="18" /></a>
            </footer>
        </article>
    </section>
    @include('auth.partials.language-panel', ['languageRoute' => 'legal.language'])
    <div class="ui-toast cms-toast" role="status" aria-live="polite" aria-atomic="true" data-ui-toast hidden><span data-ui-toast-message></span><button type="button" data-toast-dismiss aria-label="{{ __('admin.close') }}"><x-vuero.icon name="x" size="16" /></button></div>
</main>
@endsection
