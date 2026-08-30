@props(['value', 'size' => 54, 'tone' => 'primary'])

@php
    $radius = ($size - 6) / 2;
    $circumference = round(2 * M_PI * $radius, 2);
    $offset = round($circumference * (1 - max(0, min(100, (int) $value)) / 100), 2);
@endphp

<svg class="user-grid-circle-chart is-{{ $tone }}" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" aria-hidden="true">
    <circle class="user-grid-circle-chart__track" cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none" stroke-width="3" />
    <circle
        class="user-grid-circle-chart__value"
        cx="{{ $size / 2 }}"
        cy="{{ $size / 2 }}"
        r="{{ $radius }}"
        fill="none"
        stroke-width="3"
        stroke-dasharray="{{ $circumference }}"
        stroke-dashoffset="{{ $offset }}"
        transform="rotate(-90 {{ $size / 2 }} {{ $size / 2 }})"
    />
</svg>
