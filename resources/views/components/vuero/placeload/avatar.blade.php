@props([
    'size' => null,
    'centered' => false,
    'disabled' => false,
])

<div
    {{ $attributes->class([
        'placeload-avatar',
        'is-rounded-full',
        'loads' => ! $disabled,
        $size ? "is-{$size}" : null,
        'is-centered' => $centered,
    ]) }}
    aria-hidden="true"
></div>
