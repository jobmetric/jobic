@props(['user'])

@php
    $statusLabels = [
        'synced' => ['label' => 'In Sync', 'icon' => 'check', 'tone' => 'is-success'],
        'overdue' => ['label' => 'Overdue', 'icon' => 'alert-circle', 'tone' => 'is-warning'],
        'blocked' => ['label' => 'Blocked', 'icon' => 'x', 'tone' => 'is-danger'],
    ];
    $status = $statusLabels[$user['status']] ?? $statusLabels['synced'];
@endphp

<article class="column is-3" data-user-grid-item data-search="{{ Illuminate\Support\Str::lower($user['username'].' '.$user['location'].' '.$user['position'].' '.basename($user['badge'])) }}">
    <div class="grid-item-wrap">
        <div class="grid-item-head">
            <div class="flex-head">
                <div class="meta">
                    <span class="dark-inverted">{{ $status['label'] }}</span>
                    <span>{{ $user['tasksPending'] }} tasks remaining</span>
                </div>
                <div class="status-icon {{ $status['tone'] }}">
                    <x-vuero.icon :name="$status['icon']" size="10" />
                </div>
            </div>
            <div class="buttons">
                <button type="button" class="button v-button is-dark-outlined" data-user-grid-action="tasks" data-user-grid-label="{{ $user['fullName'] }}">
                    <span class="icon"><x-vuero.icon name="check-circle" size="16" /></span>
                    <span>Tasks</span>
                </button>
                <button type="button" class="button v-button is-dark-outlined" data-user-grid-action="files" data-user-grid-label="{{ $user['fullName'] }}">
                    <span class="icon"><x-vuero.icon name="file" size="16" /></span>
                    <span>Files</span>
                </button>
            </div>
        </div>
        <div class="grid-item">
            <x-vuero.layouts.flex-avatar size="big" :picture="$user['picture']" :badge="$user['badge']" />
            <h3 class="dark-inverted">{{ $user['fullName'] }}</h3>
            <p>{{ $user['position'] }}</p>
            <div class="people">
                @foreach ($user['team'] as $member)
                    <x-vuero.layouts.flex-avatar
                        size="small"
                        :picture="$member['picture']"
                        :initials="$member['initials']"
                        :color="$member['color']"
                    />
                @endforeach
            </div>
            <div class="buttons">
                <button type="button" class="button v-button is-dark-outlined" data-user-grid-action="profile" data-user-grid-label="{{ $user['fullName'] }}">
                    <span class="icon"><x-vuero.icon name="user" size="16" /></span>
                    <span>Profile</span>
                </button>
                <button type="button" class="button v-button is-dark-outlined" data-user-grid-action="talk" data-user-grid-label="{{ $user['fullName'] }}">
                    <span class="icon"><x-vuero.icon name="message" size="16" /></span>
                    <span>Talk</span>
                </button>
            </div>
        </div>
    </div>
</article>
