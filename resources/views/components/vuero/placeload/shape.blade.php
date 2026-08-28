@props([
    'width' => '100%',
    'height' => '10px',
    'disabled' => false,
    'centered' => false,
])

<div
    {{ $attributes->class(['content-shape', 'loads' => ! $disabled, 'is-centered' => $centered]) }}
    style="width: {{ $width }}; height: {{ $height }};"
    @if ($disabled) aria-hidden="true" @endif
></div>
