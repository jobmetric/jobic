@props(['user' => auth()->user(), 'menu' => false])
@php($avatarUrl = $user?->avatarUrl())
<span {{ $attributes->class(['vuero-avatar', 'vuero-avatar--menu' => $menu]) }} data-user-avatar>
    @if($avatarUrl)<img class="vuero-avatar__image" src="{{ $avatarUrl }}" alt="">@else<span data-user-avatar-fallback>{{ mb_substr($user?->name ?? '', 0, 1) }}</span>@endif
</span>
