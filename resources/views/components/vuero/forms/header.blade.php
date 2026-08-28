@props(['title', 'submit' => 'Submit', 'cancelLabel' => 'Cancel'])

<div class="form-header stuck-header" data-form-header>
    <div class="form-header-inner">
        <div class="left">
            <h3>{{ $title }}</h3>
        </div>
        <div class="right">
            <div class="buttons">
                <a class="form-button is-outlined-light" href="{{ route('vuero.admin.profile-settings') }}">
                    <x-vuero.icon name="arrow-left" size="16" />
                    <span>{{ $cancelLabel }}</span>
                </a>
                <button type="submit" class="form-button is-primary">{{ $submit }}</button>
            </div>
        </div>
    </div>
</div>
