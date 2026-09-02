<x-auth.layout title="ورود به مدیریت">
    <main class="auth-shell">
        <x-auth.visual />

        <section class="auth-panel">
            <x-auth.panel-tools />

            <div class="auth-form-wrap">
                <header class="auth-form__header">
                    <span class="auth-form__eyebrow">مدیریت Jobic</span>
                    <h1>ورود به حساب</h1>
                    <p>برای ادامه، اطلاعات حساب مدیریتی خود را وارد کنید.</p>
                </header>

                @if (session('status'))
                    <div class="alert alert--success" role="status">
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert--danger" role="alert">
                        <strong>ورود انجام نشد.</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="auth-form" method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <label class="form-field" for="email">
                        <span class="form-field__label">نشانی ایمیل</span>
                        <x-vuero.icon class="form-field__icon" name="mail" size="22" />
                        <input
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            inputmode="email"
                            required
                            autofocus
                        >
                    </label>

                    <label class="form-field" for="password">
                        <span class="form-field__label">رمز عبور</span>
                        <x-vuero.icon class="form-field__icon" name="lock" size="22" />
                        <input id="password" name="password" type="password" autocomplete="current-password" required data-password-input>
                        <button class="form-field__action" type="button" data-password-toggle aria-label="نمایش رمز عبور" aria-pressed="false">
                            <x-vuero.icon class="password-eye password-eye--show" name="eye" size="19" />
                            <x-vuero.icon class="password-eye password-eye--hide" name="eye-off" size="19" />
                        </button>
                    </label>

                    <div class="auth-form__row">
                        <label class="remember-control">
                            <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                            <span class="remember-control__track"><span><x-vuero.icon name="check" size="14" /></span></span>
                            <span>مرا به خاطر بسپار</span>
                        </label>

                        <a class="auth-form__link" href="{{ route('password.request') }}">رمز را فراموش کرده‌اید؟</a>
                    </div>

                    <button class="button button--primary auth-form__submit" type="submit">
                        ورود به پنل
                    </button>
                </form>

                <p class="auth-form__footer">
                    <a href="{{ route('legal.privacy') }}">حریم خصوصی</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('legal.cookies') }}">کوکی‌ها</a>
                </p>
            </div>
        </section>
    </main>
</x-auth.layout>
