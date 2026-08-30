@props(['value' => 0, 'tone' => null, 'size' => 54])

@php
    $radius = 16;
    $circumference = 2 * M_PI * $radius;
    $offset = $circumference - (($value / 100) * $circumference);
@endphp

<div @class(['circle-chart-wrapper', $tone ? "is-{$tone}" : null]) aria-hidden="true">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 36 36" role="presentation">
        <circle class="circle-chart__track" cx="18" cy="18" r="{{ $radius }}"></circle>
        <circle
            class="circle-chart__value"
            cx="18"
            cy="18"
            r="{{ $radius }}"
            stroke-dasharray="{{ $circumference }}"
            stroke-dashoffset="{{ $offset }}"
        ></circle>
    </svg>
</div>
