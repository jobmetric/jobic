@props(['user'])

@php
    $search = Illuminate\Support\Str::lower(implode(' ', [
        $user['username'],
        $user['position'],
        $user['location'],
        $user['industry'],
        $user['status'],
    ]));
    $statusTone = match ($user['status']) {
        'Online' => 'success',
        'Working' => 'info',
        'Suspended' => 'danger',
        default => 'muted',
    };
@endphp

<div
    class="flex-table-item"
    data-layout-item
    data-search="{{ $search }}"
    data-item-name="{{ $user['username'] }}"
>
    <div class="flex-table-cell is-media is-grow" data-th="User">
        <x-vuero.layouts.flex-avatar
            :picture="$user['picture']"
            :initials="$user['initials']"
            :color="$user['color']"
            :badge="$user['badge']"
        />
        <div>
            <span class="item-name dark-inverted">{{ $user['username'] }}</span>
            <span class="item-meta"><span>{{ $user['position'] }}</span></span>
        </div>
    </div>
    <div class="flex-table-cell" data-th="Location">
        <span class="light-text">{{ $user['location'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Industry">
        <span class="light-text">{{ $user['industry'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Status">
        <span class="flex-tag is-rounded is-{{ $statusTone }}">{{ $user['status'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Relations">
        <x-vuero.layouts.flex-avatar-stack :avatars="$user['contacts']" class="is-pushed-mobile" />
    </div>
    <div class="flex-table-cell cell-end" data-th="Actions">
        <x-vuero.layouts.flex-table-menu type="user" />
    </div>
</div>
