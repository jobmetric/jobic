@props(['row'])

@php
    $statusTone = match ($row['status']) {
        'Active' => 'success',
        'New' => 'info',
        'Suspended' => 'orange',
        default => 'muted',
    };
    $contacts = collect($row['contacts'])->map(function (array $contact): array {
        if (! empty($contact['picture'])) {
            return ['picture' => str_contains($contact['picture'], 'vuero-1.svg')
                ? '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg'
                : '/vuero/images/layouts/list-view-v1/avatars/'.basename($contact['picture'])];
        }

        return ['initials' => $contact['initials'], 'color' => str_replace('h-', '', $contact['color'] ?? 'info')];
    })->all();
@endphp

<div
    class="flex-table-item"
    data-layout-item
    data-projects-row
    data-username="{{ $row['username'] }}"
    data-position="{{ $row['position'] }}"
    data-earnings="{{ $row['annualEarnings'] }}"
    data-status="{{ $row['status'] }}"
    data-contacts="{{ count($row['contacts']) }}"
>
    <div class="flex-table-cell is-media is-grow" data-th="Name">
        <x-vuero.layouts.flex-avatar
            :picture="$row['picture']"
            :badge="$row['badge']"
        />
        <div>
            <span class="item-name dark-inverted">{{ $row['username'] }}</span>
            <span class="item-meta"><span>{{ $row['position'] }}</span></span>
        </div>
    </div>
    <div class="flex-table-cell" data-th="Position">
        <span class="light-text">{{ $row['position'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Revenue">
        <span class="light-text">${{ number_format($row['annualEarnings']) }}</span>
    </div>
    <div class="flex-table-cell" data-th="Status">
        <span class="flex-tag is-rounded is-{{ $statusTone }}">{{ $row['status'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Contacts">
        <x-vuero.layouts.flex-avatar-stack :avatars="$contacts" class="is-pushed-mobile" />
    </div>
    <div class="flex-table-cell cell-end" data-th="Actions">
        <x-vuero.layouts.flex-table-menu type="user" />
    </div>
</div>
