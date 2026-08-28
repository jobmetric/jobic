@props(['user'])

<article class="column is-4" data-tile-item data-search="{{ Illuminate\Support\Str::lower($user['username'].' '.$user['position'].' '.basename($user['badge'])) }}">
    <div class="tile-grid-item">
        <div class="tile-grid-item-inner">
            <x-vuero.layouts.flex-avatar
                size="medium"
                :picture="$user['picture']"
                :initials="$user['initials']"
                :color="$user['color']"
                :badge="$user['badge']"
            />
            <div class="meta">
                <span class="dark-inverted">{{ $user['username'] }}</span>
                <span>{{ $user['position'] }}</span>
            </div>
            <x-vuero.layouts.user-card-dropdown variant="tile" />
        </div>
    </div>
</article>
