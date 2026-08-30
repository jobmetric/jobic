@props(['icon' => null, 'expanded' => false])

<div {{ $attributes->class(['control', 'has-icon' => $icon, 'is-expanded' => $expanded]) }}>
    {{ $slot }}
    @if ($icon)
        <span class="form-icon" aria-hidden="true">
            <x-vuero.icon :name="$icon" size="18" />
        </span>
    @endif
</div>
