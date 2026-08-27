@props(['src', 'name', 'squared' => false, 'size' => 38])

<img
    {{ $attributes->class(['widget-avatar', 'is-squared' => $squared]) }}
    src="{{ $src }}"
    alt="{{ $name }}"
    width="{{ $size }}"
    height="{{ $size }}"
    loading="lazy"
>
