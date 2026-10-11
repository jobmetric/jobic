@props([
    'name',
    'size' => 20,
    'label' => null,
])

@php
    static $iconPaths = [];
    $iconName = preg_match('/\A[a-z0-9-]+\z/', (string) $name) ? $name : '';

    if (! array_key_exists($iconName, $iconPaths)) {
        $iconFile = $iconName !== '' ? resource_path('icons/jobic/'.$iconName.'.svg') : '';
        $iconSvg = $iconFile !== '' && is_file($iconFile) ? file_get_contents($iconFile) : false;
        $iconPaths[$iconName] = is_string($iconSvg) && preg_match('/<svg\b[^>]*>(.*)<\/svg>/s', $iconSvg, $matches)
            ? $matches[1]
            : '';
    }
@endphp

<svg
    {{ $attributes->class('ui-icon') }}
    width="{{ $size }}"
    height="{{ $size }}"
    style="width: {{ $size }}px; height: {{ $size }}px;"
    data-icon="{{ $iconName }}"
    viewBox="0 0 24 24"
    fill="none"
    aria-hidden="{{ $label ? 'false' : 'true' }}"
    @if ($label) role="img" aria-label="{{ $label }}" @endif
>
    {!! $iconPaths[$iconName] !!}
</svg>
