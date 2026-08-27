@props(['project'])

@php
    $search = Illuminate\Support\Str::lower(implode(' ', [
        $project['name'],
        $project['customer'],
        $project['industry'],
        $project['status'],
        $project['duration'],
    ]));
@endphp

<tr
    data-layout-item
    data-search="{{ $search }}"
    data-item-name="{{ $project['name'] }}"
>
    <td>{{ $project['id'] }}</td>
    <td>
        <div class="flex-media">
            <x-vuero.layouts.flex-avatar :picture="$project['picture']" />
            <div class="meta">
                <h3>{{ $project['name'] }}</h3>
                <span>{{ $project['duration'] }}</span>
            </div>
        </div>
    </td>
    <td>{{ $project['customer'] }}</td>
    <td>{{ $project['industry'] }}</td>
    <td><span class="flex-tag is-rounded">{{ $project['status'] }}</span></td>
    <td>
        <x-vuero.layouts.flex-avatar-stack :avatars="$project['team']" :limit="3" />
    </td>
    <td>
        <x-vuero.layouts.flex-table-menu type="project" />
    </td>
</tr>
