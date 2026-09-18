<div class="form-field">
    <label class="form-field__label" for="email">{{ __('auth.screen.email') }}</label>
    <x-vuero.icon class="form-field__icon" name="mail" size="22" />
    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="username" inputmode="email" dir="ltr" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
</div>
@error('email')<small class="auth-field-error" id="email-error">{{ $message }}</small>@enderror
