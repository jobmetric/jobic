@extends('common.base')

@php
    $code = (string) $code;
    $messageKey = \Illuminate\Support\Facades\Lang::has('errors.'.$code.'.title') ? $code : ((int) $code >= 500 ? '5xx' : '4xx');
    $signIn = in_array($code, ['401', '419'], true);
    $art = match ($code) {
        '401', '403' => 'access',
        '408', '419', '429', '504' => 'pause',
        '503' => 'having-coffee',
        default => (int) $code >= 500 ? 'error-5' : 'error-2',
    };
    // Error rendering must not query the language database or retain a failed page's registries.
    \JobMetric\Domi\Facades\Domi::clearResolvedInstance(\JobMetric\Domi\Domi::class);
    app()->forgetInstance(\JobMetric\Domi\Domi::class);
    \JobMetric\Domi\Facades\Domi::setTitle(__('errors.'.$messageKey.'.title'));
    \JobMetric\Domi\Facades\Domi::setSiteName(config('app.name', 'Jobic'));
@endphp

@section('direction', config('localization.bootstrap.'.app()->getLocale().'.direction', 'ltr'))
@section('body-class', 'jobic-error-page')
@section('domi-head')
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <link rel="stylesheet" href="{{ asset('css/errors.css') }}">
    @cmsHook('jobic.page.head.end')
@endsection

@section('body')
<div class="error-scene error-scene--{{ $code }} error-scene--{{ $art }}">
    <header class="error-header">
        <a class="error-brand" href="{{ url('/') }}" aria-label="{{ __('errors.home') }}"><x-brand /></a>
        <span class="error-status">{{ __('errors.code', ['code' => $code]) }}</span>
    </header>
    <main class="error-main" aria-labelledby="error-title">
        <div class="error-art" aria-hidden="true">
            <span class="error-orbit"></span>
            <span class="error-number" dir="ltr">{{ $code }}</span>
            <div class="error-illustration">
                @if($art === 'access')
                    <x-error-access />
                @elseif($art === 'pause')
                    <x-vuero.svg.error-placeholder />
                @else
                    <img class="error-image-light" src="{{ asset('vuero/images/illustrations/placeholders/'.$art.'.svg') }}" alt="" width="460" height="340">
                    <img class="error-image-dark" src="{{ asset('vuero/images/illustrations/placeholders/'.$art.'-dark.svg') }}" alt="" width="460" height="340">
                @endif
            </div>
        </div>
        <div class="error-copy">
            <span class="error-eyebrow"><span></span>{{ __('errors.'.$messageKey.'.label') }}</span>
            <h1 id="error-title">{{ __('errors.'.$messageKey.'.title') }}</h1>
            <p class="error-description">{{ __('errors.'.$messageKey.'.description') }}</p>
            <nav class="error-actions" aria-label="{{ __('errors.home') }}">
                <a class="error-primary" href="{{ $signIn ? url('/login') : url('/') }}">
                    {{ __($signIn ? ($code === '401' ? 'errors.sign_in' : 'errors.login') : 'errors.home') }}
                    <span aria-hidden="true" class="error-arrow">&rarr;</span>
                </a>
                @if($signIn)<a class="error-secondary" href="{{ url('/') }}">{{ __('errors.home') }}</a>@endif
            </nav>
            <p class="error-hint">{{ __('errors.'.$messageKey.'.hint') }}</p>
        </div>
    </main>
    <footer class="error-footer">{{ __('errors.footer') }}<span aria-hidden="true">✦</span></footer>
</div>
@endsection
