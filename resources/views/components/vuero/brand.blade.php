@props([
    'compact' => false,
])

<span {{ $attributes->class(['brand', 'brand--compact' => $compact]) }}>
    <svg class="brand__mark" viewBox="0 0 160 160" aria-hidden="true">
        <path class="brand__mark-right" d="M67.3 55.7 75.6 70l3.7 6.4 22.1 38.3 35.9-.1L78.2 14.1 61.2 45l6.1 10.7Zm62.8 58.8-21.3.1" />
        <path class="brand__mark-left" d="m39.1 145.9 11.7-20.3 2.7-4.7 3.7-6.4 22.1-38.3L61.2 45 3.6 145.9h35.5Zm25.7-94.4 2.5 4.2 8.3 14.2v.1L64.8 51.5Z" />
        <path class="brand__mark-bottom" d="M39 145.9h117.4l-19.1-31.4-80.1.1L39 145.9Zm14.4-24.9-10.6 18.5 7.9-13.9 2.7-4.6Z" />
    </svg>
    @unless ($compact)
        <span class="brand__name">Jobic</span>
    @endunless
</span>
