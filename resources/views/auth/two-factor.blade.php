@extends('common.auth-recovery')
@section('title', __('two-factor.challenge_title'))
@section('recovery-title', __('two-factor.challenge_title'))
@section('recovery-description', __('two-factor.challenge_intro'))
@section('recovery-form')
<form class="auth-form" method="POST" action="{{ route('login.two-factor.store') }}">
    @csrf
    <div class="form-field">
        <label class="form-field__label" for="code">{{ __('two-factor.code') }}</label>
        <x-vuero.icon class="form-field__icon" name="shield" size="22" />
        <input id="code" name="code" type="text" dir="ltr" autocomplete="one-time-code" maxlength="16" required autofocus @error('code') aria-invalid="true" aria-describedby="code-error" @enderror />
    </div>
    @error('code')<small class="auth-field-error" id="code-error">{{ $message }}</small>@enderror
    <button class="button button--primary auth-form__submit" type="submit">{{ __('two-factor.verify') }}<x-vuero.icon name="arrow-right" size="18" /></button>
</form>
@endsection
