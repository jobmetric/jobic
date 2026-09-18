@extends('common.auth-recovery')
@section('title', __('auth.recovery.reset_title'))
@section('recovery-title', __('auth.recovery.reset_title'))
@section('recovery-description', __('auth.recovery.reset_description'))
@section('recovery-form')
<form class="auth-form" method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    @include('auth.partials.recovery-email')
    @foreach(['password' => 'new_password', 'password_confirmation' => 'confirm_password'] as $field => $label)
    <div class="form-field login-password-field">
        <label class="form-field__label" for="{{ $field }}">{{ __('auth.recovery.'.$label) }}</label>
        <x-vuero.icon class="form-field__icon" name="lock" size="22" />
        <input id="{{ $field }}" name="{{ $field }}" type="password" autocomplete="new-password" required data-password-input @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
        <button class="form-field__action" type="button" data-password-toggle tabindex="-1" aria-label="{{ __('auth.screen.show') }}" aria-pressed="false"><x-vuero.icon class="password-eye password-eye--show" name="eye" size="19" /><x-vuero.icon class="password-eye password-eye--hide" name="eye-off" size="19" /></button>
    </div>
    @error($field)<small class="auth-field-error" id="{{ $field }}-error">{{ $message }}</small>@enderror
    @endforeach
    <button class="button button--primary auth-form__submit" type="submit">{{ __('auth.recovery.save') }}<x-vuero.icon name="arrow-right" size="18" /></button>
</form>
@endsection
