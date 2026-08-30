@props(['project'])

<a href="#" class="grid-item" data-project-card>
    <div class="item-head">
        <span class="type">{{ $project['type'] }}</span>
        <span @class(['status', $project['status_class']])>{{ $project['status'] }}</span>
    </div>
    <div class="item-title">
        <h3>{{ $project['title'] }}</h3>
        <div class="tags">
            @foreach ($project['tags'] as $tag)
                <span class="flex-tag is-rounded is-solid">{{ $tag }}</span>
            @endforeach
        </div>
    </div>
    <div class="item-description">
        <p>{{ $project['description'] }}</p>
        <div class="item-people">
            <x-vuero.layouts.flex-avatar-stack :avatars="$project['team']" :limit="3" />
            <span class="label">{{ $project['remaining'] }}</span>
        </div>
    </div>
</a>
