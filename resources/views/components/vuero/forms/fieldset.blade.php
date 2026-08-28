@props(['title', 'subtitle' => null])

<div class="form-fieldset">
    <div class="fieldset-heading">
        <h4>{{ $title }}</h4>
        @if ($subtitle)
            <p>{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
