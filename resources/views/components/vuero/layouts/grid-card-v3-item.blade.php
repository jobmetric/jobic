@props(['project'])

<article class="column is-4" data-grid-item data-search="{{ Illuminate\Support\Str::lower($project['name'].' '.$project['remaining']) }}">
    <div class="card-grid-item">
        @if ($project['lockable'])
            <label class="h-toggle">
                <input type="checkbox" data-grid-lock @checked($project['locked'])>
                <span class="toggler">
                    <span class="active"><x-vuero.icon name="lock" size="14" /></span>
                    <span class="inactive"><x-vuero.icon name="check" size="14" /></span>
                </span>
            </label>
        @endif

        <x-vuero.layouts.flex-avatar size="large" squared :picture="$project['image']" :badge="$project['badge']" />

        <h3 class="dark-inverted">{{ $project['name'] }}</h3>
        <p>{{ $project['remaining'] }} remaining</p>
        <div class="description"><p>{{ $project['description'] }}</p></div>

        <div class="people">
            @foreach ($project['team'] as $member)
                <x-vuero.layouts.flex-avatar
                    size="small"
                    :picture="$member['picture'] ?? null"
                    :initials="$member['initials'] ?? null"
                    :color="$member['color'] ?? null"
                />
            @endforeach
        </div>

        <div class="buttons">
            <button type="button" class="button v-button is-dark-outlined" data-grid-action="view" data-grid-label="{{ $project['name'] }}">
                <span class="icon"><x-vuero.icon name="eye" size="16" /></span>
                <span>View</span>
            </button>
            <button type="button" class="button v-button is-dark-outlined" data-grid-action="edit" data-grid-label="{{ $project['name'] }}">
                <span class="icon"><x-vuero.icon name="edit" size="16" /></span>
                <span>Edit</span>
            </button>
        </div>
    </div>
</article>
