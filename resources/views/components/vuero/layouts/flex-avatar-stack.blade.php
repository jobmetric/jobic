@props(['avatars', 'limit' => 3])

@php
    $visible = array_slice($avatars, 0, $limit);
    $remaining = max(count($avatars) - $limit, 0);
@endphp

<div {{ $attributes->class(['flex-avatar-stack']) }} aria-label="{{ count($avatars) }} relations">
    @foreach ($visible as $avatar)
        @if (! empty($avatar['picture']))
            <img src="{{ $avatar['picture'] }}" alt="">
        @else
            <span class="is-{{ str_replace('h-', '', $avatar['color'] ?? 'info') }}">{{ $avatar['initials'] }}</span>
        @endif
    @endforeach
    @if ($remaining > 0)
        <span class="flex-avatar-stack__more">+{{ $remaining }}</span>
    @endif
</div>
