@props(['user'])

<article class="column is-3" data-user-grid-item data-search="{{ Illuminate\Support\Str::lower($user['username'].' '.$user['location'].' '.$user['position'].' '.basename($user['badge'])) }}">
    <div class="grid-item">
        <x-vuero.layouts.user-card-dropdown variant="tile" />
        <x-vuero.layouts.flex-avatar size="big" :picture="$user['avatar']" :badge="$user['badge']" />
        <h3 class="dark-inverted">{{ $user['fullName'] }}</h3>
        <p>{{ $user['position'] }}</p>
        <div class="button-wrap has-text-centered">
            @if ($user['added'])
                <button type="button" class="button v-button is-primary is-raised" data-user-grid-action="add-project" data-user-grid-label="{{ $user['fullName'] }}">Add To Project</button>
            @else
                <button type="button" class="button v-button is-dark-outlined is-raised" data-user-grid-action="add-team" data-user-grid-label="{{ $user['fullName'] }}">Add To Team</button>
            @endif
            <div>
                <button type="button" class="dark-inverted-hover" data-user-grid-action="profile" data-user-grid-label="{{ $user['fullName'] }}">View Profile</button>
            </div>
        </div>
    </div>
</article>
