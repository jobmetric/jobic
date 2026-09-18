@extends('common.auth', ['authLocale' => app()->getLocale(), 'script' => 'resources/js/vuero/pages/auth/login.js'])
@section('title', trans('auth.screen.title'))
@section('content')
    <main class="auth-shell auth-login-refined" data-auth-shell>
        @include('auth.partials.welcome')
        <section class="auth-panel">
            @include('auth.partials.tools')
            <div class="auth-form-wrap">
                <header class="auth-form__header">
                    <h1>{{ trans('auth.screen.heading') }}</h1>
                    <p>{{ trans('auth.screen.description') }}</p>
                </header>

                @if (session('status'))
                    <div class="alert alert--success" role="status">
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert--danger" role="alert">
                        <strong>{{ trans('auth.screen.failed') }}</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="auth-form" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <label class="form-field" for="email">
                        <span class="form-field__label">{{ trans('auth.screen.email') }}</span>
                        <x-vuero.icon class="form-field__icon" name="mail" size="22" />
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            inputmode="email"
                            dir="ltr"
                            required
                            autofocus
                        >
                    </label>

                    <div class="form-field login-password-field">
                        <label class="form-field__label" for="password">{{ trans('auth.screen.password') }}</label>
                        <x-vuero.icon class="form-field__icon" name="lock" size="22" />
                        <input id="password" name="password" type="password" autocomplete="current-password" required data-password-input>
                        <button class="form-field__action" type="button" data-password-toggle tabindex="-1" aria-label="{{ trans('auth.screen.show') }}" aria-pressed="false">
                            <x-vuero.icon class="password-eye password-eye--show" name="eye" size="19" />
                            <x-vuero.icon class="password-eye password-eye--hide" name="eye-off" size="19" />
                        </button>
                    </div>

                    <div class="auth-form__row">
                        <label class="remember-control">
                            <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                            <span class="remember-control__track"><span><x-vuero.icon name="check" size="14" /></span></span>
                            <span>{{ trans('auth.screen.remember') }}</span>
                        </label>

                        <a class="auth-form__link" href="{{ route('password.request') }}">{{ trans('auth.screen.forgot') }}</a>
                    </div>

                    <button class="button button--primary auth-form__submit" type="submit">
                        {{ trans('auth.screen.submit') }} <span class="login-submit-arrow" aria-hidden="true">↗</span>
                    </button>
                </form>

                <p class="auth-form__footer">
                    <a href="{{ route('legal.privacy') }}">{{ trans('auth.screen.privacy') }}</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('legal.cookies') }}">{{ trans('auth.screen.cookies') }}</a>
                </p>
            </div>
        </section>
        @include('auth.partials.language-panel')
        <div class="ui-toast cms-toast" role="status" aria-live="polite" aria-atomic="true" data-ui-toast hidden><x-vuero.icon name="check" size="17" /><span data-ui-toast-message></span><button type="button" data-toast-dismiss aria-label="{{ __('admin.close') }}"><x-vuero.icon name="x" size="16" /></button></div>
    </main>
@endsection
