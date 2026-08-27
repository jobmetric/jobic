@props(['user'])

@php
    $search = Illuminate\Support\Str::lower(implode(' ', [
        $user['username'],
        $user['position'],
        $user['location'],
        $user['industry'],
        $user['status'],
    ]));
@endphp

<tr
    data-layout-item
    data-search="{{ $search }}"
    data-item-name="{{ $user['username'] }}"
>
    <td>{{ $user['id'] }}</td>
    <td>
        <div class="flex-media">
            <x-vuero.layouts.flex-avatar
                :picture="$user['picture']"
                :initials="$user['initials']"
                :color="$user['color']"
                :badge="$user['badge']"
            />
            <div class="meta">
                <h3>{{ $user['username'] }}</h3>
                <span>{{ $user['position'] }}</span>
            </div>
        </div>
    </td>
    <td>{{ $user['location'] }}</td>
    <td>{{ $user['industry'] }}</td>
    <td>
        <x-vuero.layouts.flex-avatar-stack :avatars="$user['contacts']" :limit="3" />
    </td>
    <td>
        <x-vuero.layouts.flex-table-menu type="user" />
    </td>
</tr>
