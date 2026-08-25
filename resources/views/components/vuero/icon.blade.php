@props([
    'name',
    'size' => 20,
    'label' => null,
])

<svg
    {{ $attributes->class('ui-icon') }}
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="{{ $label ? 'false' : 'true' }}"
    @if ($label) role="img" aria-label="{{ $label }}" @endif
>
    <use href="#icon-{{ $name }}"></use>
</svg>
