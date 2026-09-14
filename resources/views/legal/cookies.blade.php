@extends('common.auth')
@section('title', 'کوکی‌ها')
@section('content')
    <main class="auth-shell auth-shell--legal">
        <section class="auth-panel auth-panel--legal">
            <x-auth.panel-tools />

            <article class="legal-content">
                <header class="auth-form__header">
                    <span class="auth-form__eyebrow">Jobic CMS</span>
                    <h1>سیاست کوکی</h1>
                </header>

                <section>
                    <h2>کوکی‌های ضروری</h2>
                    <p>این سامانه برای ورود و محافظت در برابر CSRF از کوکی نشست و توکن CSRF استفاده می‌کند. این کوکی‌ها برای عملکرد پایه ضروری هستند.</p>
                </section>

                <section>
                    <h2>مرا به خاطر بسپار</h2>
                    <p>اگر گزینه «مرا به خاطر بسپار» را انتخاب کنید، یک کوکی احراز هویت با مدت حداکثر ۳۰ روز ذخیره می‌شود. بدون انتخاب شما، این کوکی ایجاد نمی‌شود.</p>
                </section>

                <section>
                    <h2>کوکی‌های تحلیلی</h2>
                    <p>در صفحات ورود و احراز هویت، کوکی تحلیلی یا بازاریابی استفاده نمی‌شود.</p>
                </section>

                <p class="auth-form__footer">
                    <a href="{{ route('login') }}">بازگشت به ورود</a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ route('legal.privacy') }}">حریم خصوصی</a>
                </p>
            </article>
        </section>
    </main>
@endsection
