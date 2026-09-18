@extends('common.auth', ['script' => 'resources/js/vuero/pages/auth/recovery.js'])
@section('content')
<main class="auth-shell auth-login-refined auth-recovery" data-auth-shell>
    @include('auth.partials.welcome')
    <section class="auth-panel">
        @include('auth.partials.tools')
        <div class="auth-form-wrap">
            <header class="auth-form__header"><h1>@yield('recovery-title')</h1><p>@yield('recovery-description')</p></header>
            @if(session('status'))<div class="alert alert--success" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="alert alert--danger" role="alert">{{ $errors->first() }}</div>@endif
            @cmsHook('jobic.auth.recovery.form.before')
            @yield('recovery-form')
            @cmsHook('jobic.auth.recovery.form.after')
            <p class="auth-form__footer"><a class="auth-recovery-back" href="{{ route('login') }}"><x-vuero.icon name="arrow-left" size="18" />{{ __('auth.recovery.back') }}</a></p>
        </div>
    </section>
    @include('auth.partials.language-panel')
    <div class="ui-toast cms-toast" role="status" aria-live="polite" data-ui-toast hidden><span data-ui-toast-message></span><button type="button" data-toast-dismiss aria-label="{{ __('admin.close') }}"><x-vuero.icon name="x" /></button></div>
</main>
@endsection
