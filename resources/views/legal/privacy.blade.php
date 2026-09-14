@extends('common.auth')
@section('title', 'حریم خصوصی')
@section('content')
    <main class="auth-shell auth-shell--legal">
        <section class="auth-panel auth-panel--legal">
            <x-auth.panel-tools />

            <article class="legal-content">
                <header class="auth-form__header">
                    <span class="auth-form__eyebrow">Jobic CMS</span>
                    <h1>حریم خصوصی</h1>
                </header>

                <section>
                    <h2>داده‌هایی که جمع‌آوری می‌کنیم</h2>
                    <p>برای ورود به پنل مدیریت، نشانی ایمیل، رمز عبور (به‌صورت هش‌شده)، آدرس IP و عامل کاربر (user-agent) پردازش می‌شود.</p>
                </section>

                <section>
                    <h2>هدف پردازش</h2>
                    <p>احراز هویت، مدیریت نشست، امنیت حساب و جلوگیری از دسترسی غیرمجاز.</p>
                </section>

                <section>
                    <h2>مبنای قانونی (GDPR)</h2>
                    <ul>
                        <li>اجرای قرارداد (ورود به سرویس مدیریت)</li>
                        <li>منافع مشروع (ثبت رویدادهای امنیتی ورود)</li>
                    </ul>
                </section>

                <section>
                    <h2>مدت نگهداری</h2>
                    <p>رویدادهای ورود حداکثر ۹۰ روز نگهداری و سپس حذف می‌شوند. داده حساب کاربری تا زمان حذف حساب باقی می‌ماند.</p>
                </section>

                <p class="auth-form__footer">
                    <a href="{{ route('login') }}">بازگشت به ورود</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('legal.cookies') }}">کوکی‌ها</a>
                </p>
            </article>
        </section>
    </main>
@endsection
