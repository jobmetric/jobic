@extends('common.auth', ['script' => 'resources/js/vuero/pages/auth/login.js'])
@section('title', 'تعیین رمز جدید')
@section('content')
    <main class="auth-shell">
        <x-auth.visual />

        <section class="auth-panel">
            <x-auth.panel-tools />

            <div class="auth-form-wrap">
                <header class="auth-form__header">
                    <span class="auth-form__eyebrow">مدیریت Jobic</span>
                    <h1>رمز عبور جدید</h1>
                    <p>رمز عبور جدید خود را وارد کنید.</p>
                </header>

                @if ($errors->any())
                    <div class="alert alert--danger" role="alert">
                        <strong>بازنشانی انجام نشد.</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="auth-form" method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <label class="form-field" for="email">
                        <span class="form-field__label">نشانی ایمیل</span>
                        <x-vuero.icon class="form-field__icon" name="mail" size="22" />
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $email) }}"
                            autocomplete="username"
                            inputmode="email"
                            required
                            autofocus
                        >
                    </label>

                    <label class="form-field" for="password">
                        <span class="form-field__label">رمز عبور جدید</span>
                        <x-vuero.icon class="form-field__icon" name="lock" size="22" />
                        <input id="password" name="password" type="password" autocomplete="new-password" required data-password-input>
                        <button class="form-field__action" type="button" data-password-toggle aria-label="نمایش رمز عبور" aria-pressed="false">
                            <x-vuero.icon class="password-eye password-eye--show" name="eye" size="19" />
                            <x-vuero.icon class="password-eye password-eye--hide" name="eye-off" size="19" />
                        </button>
                    </label>

                    <label class="form-field" for="password_confirmation">
                        <span class="form-field__label">تکرار رمز عبور</span>
                        <x-vuero.icon class="form-field__icon" name="lock" size="22" />
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>
                    </label>

                    <button class="button button--primary auth-form__submit" type="submit">
                        ذخیره رمز جدید
                    </button>
                </form>

                <p class="auth-form__footer">
                    <a href="{{ route('login') }}">بازگشت به ورود</a>
                </p>
            </div>
        </section>
    </main>
@endsection
