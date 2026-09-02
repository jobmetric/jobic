<x-auth.layout title="فراموشی رمز عبور">
    <main class="auth-shell">
        <x-auth.visual />

        <section class="auth-panel">
            <x-auth.panel-tools />

            <div class="auth-form-wrap">
                <header class="auth-form__header">
                    <span class="auth-form__eyebrow">مدیریت Jobic</span>
                    <h1>بازنشانی رمز عبور</h1>
                    <p>نشانی ایمیل حساب خود را وارد کنید تا لینک بازنشانی برایتان ارسال شود.</p>
                </header>

                @if (session('status'))
                    <div class="alert alert--success" role="status">
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert--danger" role="alert">
                        <strong>درخواست انجام نشد.</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="auth-form" method="POST" action="{{ route('password.email') }}">
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

                    <button class="button button--primary auth-form__submit" type="submit">
                        ارسال لینک بازنشانی
                    </button>
                </form>

                <p class="auth-form__footer">
                    <a href="{{ route('login') }}">بازگشت به ورود</a>
                </p>
            </div>
        </section>
    </main>
</x-auth.layout>
