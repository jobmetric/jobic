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

<div
    class="flex-table-item"
    data-layout-item
    data-search="{{ $search }}"
    data-item-name="{{ $project['name'] }}"
>
    <div class="flex-table-cell is-media is-grow" data-th="Project">
        <x-vuero.layouts.flex-avatar :picture="$project['picture']" />
        <div>
            <span class="item-name dark-inverted">{{ $project['name'] }}</span>
            <span class="item-meta">
                <span><x-vuero.icon name="clock" size="14" />{{ $project['duration'] }}</span>
            </span>
        </div>
    </div>
    <div class="flex-table-cell" data-th="Customer">
        <span class="light-text">{{ $project['customer'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Industry">
        <span class="light-text">{{ $project['industry'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Status">
        <span class="flex-tag is-rounded">{{ $project['status'] }}</span>
    </div>
    <div class="flex-table-cell h-hidden-tablet-p" data-th="Team">
        <x-vuero.layouts.flex-avatar-stack :avatars="$project['team']" class="is-pushed-mobile" />
    </div>
    <div class="flex-table-cell cell-end" data-th="Actions">
        <x-vuero.layouts.flex-table-menu type="project" />
    </div>
</div>
