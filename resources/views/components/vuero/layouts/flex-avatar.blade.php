@props(['picture' => null, 'initials' => null, 'color' => null, 'badge' => null, 'size' => 'medium', 'squared' => false])

@php
    $sizeClass = match ($size) {
        'small' => 'is-small',
        'big' => 'is-big',
        'large' => 'is-large',
        default => 'is-medium',
    };
@endphp

<span {{ $attributes->class(['flex-avatar', $sizeClass, 'is-squared' => $squared]) }}>
    @if ($picture)
        <img class="flex-avatar__image" src="{{ $picture }}" alt="">
    @elseif ($initials)
        <span class="flex-avatar__initials is-{{ str_replace('h-', '', $color ?? 'info') }}">{{ $initials }}</span>
    @endif
    @if ($badge)
        <img class="flex-avatar__badge" src="{{ $badge }}" alt="">
    @endif
</span>
