@props([
    'initials' => 'VU',
    'avatar' => null,
    'status' => null,
])

<span {{ $attributes->class(['messaging-avatar']) }} aria-hidden="true">
    @if ($avatar)
        <img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="" draggable="false">
    @else
        <span>{{ $initials }}</span>
    @endif
    @if ($status)<i class="is-{{ $status }}"></i>@endif
</span>
