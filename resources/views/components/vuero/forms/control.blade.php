@props(['icon' => null])

<div {{ $attributes->class(['control', 'has-icon' => $icon]) }}>
    {{ $slot }}
    @if ($icon)
        <span class="form-icon" aria-hidden="true">
            <x-vuero.icon :name="$icon" size="18" />
        </span>
    @endif
</div>
