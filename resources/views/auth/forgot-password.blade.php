@extends('common.auth-recovery')
@section('title', __('auth.recovery.request_title'))
@section('recovery-title', __('auth.recovery.request_title'))
@section('recovery-description', __('auth.recovery.request_description'))
@section('recovery-form')
<form class="auth-form" method="POST" action="{{ route('password.email') }}">
    @csrf
    @include('auth.partials.recovery-email', ['email' => ''])
    <button class="button button--primary auth-form__submit" type="submit">{{ __('auth.recovery.send') }}<x-vuero.icon name="arrow-right" size="18" /></button>
</form>
@endsection
