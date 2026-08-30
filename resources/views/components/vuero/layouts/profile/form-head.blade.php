@props(['title', 'subtitle', 'backUrl', 'tab' => null])

<div class="form-head stuck-header" data-profile-form-head>
    <div class="form-head-inner">
        <div class="left">
            <h3>{{ $title }}</h3>
            <p>{{ $subtitle }}</p>
        </div>
        <div class="right">
            <div class="buttons">
                <a class="form-button is-outlined-light" href="{{ $backUrl }}">
                    <x-vuero.icon name="arrow-left" size="16" />
                    <span>Go Back</span>
                </a>
                <button type="button" class="form-button is-primary" data-profile-save>Save Changes</button>
            </div>
        </div>
    </div>
</div>
