<x-vuero.layouts.auth title="ورود به مدیریت" script="resources/js/vuero/pages/auth/login.js">
    <main class="auth-shell">
        <section class="auth-visual" aria-hidden="true">
            <div class="auth-visual__halo"></div>
            <svg class="auth-visual__illustration" viewBox="0 0 720 580" fill="none">
                <path d="m360 55 267 154v218L360 581 93 427V209L360 55Z" fill="var(--auth-art-base)" opacity=".55" />
                <path d="m360 92 227 131v176L360 530 133 399V223L360 92Z" fill="var(--auth-art-surface)" />
                <path d="m360 92 227 131-227 132-227-132L360 92Z" fill="var(--auth-art-top)" />
                <rect x="244" y="153" width="232" height="157" rx="12" fill="var(--auth-art-dark)" transform="rotate(30 244 153) skewX(-30)" opacity=".08" />
                <path d="m260 191 190 110v116L260 307V191Z" fill="var(--auth-art-dark)" />
                <path d="m450 301 90-52v116l-90 52V301Z" fill="var(--auth-art-panel)" />
                <path d="m260 191 90-52 190 110-90 52-190-110Z" fill="var(--auth-art-muted)" />
                <path d="m286 230 136 79" stroke="var(--primary)" stroke-width="9" stroke-linecap="round" />
                <path d="m286 260 104 60M286 291l78 45" stroke="var(--auth-art-line)" stroke-width="7" stroke-linecap="round" />
                <circle cx="485" cy="326" r="27" fill="var(--primary)" opacity=".9" />
                <path d="m475 326 8 8 14-18" stroke="white" stroke-width="6" stroke-linecap="round" stroke-linejoin="round" />
                <path d="m182 373 88-51 87 50-88 51-87-50Z" fill="var(--auth-art-muted)" />
                <path d="m182 373 87 50v58l-87-50v-58Z" fill="var(--auth-art-dark)" />
                <path d="m269 423 88-51v58l-88 51v-58Z" fill="var(--auth-art-panel)" />
                <circle cx="204" cy="389" r="7" fill="var(--primary)" />
                <circle cx="229" cy="403" r="7" fill="#ffb26b" />
                <circle cx="254" cy="418" r="7" fill="#6b7dff" />
            </svg>
        </section>

        <section class="auth-panel">
            <div class="auth-panel__tools">
                <a href="{{ route('home') }}" aria-label="صفحه اصلی">
                    <x-vuero.brand compact />
                </a>

                <button class="icon-button theme-toggle" type="button" data-theme-toggle aria-label="تغییر پوسته" aria-pressed="false">
                    <x-vuero.icon class="theme-toggle__sun" name="sun" />
                    <x-vuero.icon class="theme-toggle__moon" name="moon" />
                </button>
            </div>

            <div class="auth-form-wrap">
                <header class="auth-form__header">
                    <span class="auth-form__eyebrow">مدیریت Jobic</span>
                    <h1>ورود به حساب</h1>
                    <p>برای ادامه، اطلاعات حساب مدیریتی خود را وارد کنید.</p>
                </header>

                @if ($errors->any())
                    <div class="alert alert--danger" role="alert">
                        <strong>ورود انجام نشد.</strong>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form class="auth-form" method="POST" action="{{ route('vuero.login.store') }}">
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

                    <label class="remember-control">
                        <input name="remember" type="checkbox" value="1" @checked(old('remember'))>
                        <span class="remember-control__track"><span><x-vuero.icon name="check" size="14" /></span></span>
                        <span>مرا به خاطر بسپار</span>
                    </label>

                    <button class="button button--primary auth-form__submit" type="submit">
                        ورود به پنل
                    </button>
                </form>

                <p class="auth-form__footer">Jobic CMS · محیط مدیریت امن و یکپارچه</p>
            </div>
        </section>
    </main>
</x-vuero.layouts.auth>
