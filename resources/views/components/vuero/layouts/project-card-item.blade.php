@props(['project'])

<div class="grid-item" data-project-card>
    <div class="top-section">
        <div class="head">
            <h3>{{ $project['title'] }}</h3>
            <x-vuero.layouts.flex-table-menu type="project" />
        </div>
        <div class="body">
            <p>{{ $project['description'] }}</p>
        </div>
    </div>
    <div class="bottom-section">
        <div class="foot-block">
            <h4 class="heading">Developers</h4>
            <div class="developers">
                @foreach ($project['developers'] as $developer)
                    @if (! empty($developer['picture']))
                        <x-vuero.layouts.flex-avatar :picture="$developer['picture']" size="small" />
                    @else
                        <x-vuero.layouts.flex-avatar :initials="$developer['initials']" :color="$developer['color']" size="small" />
                    @endif
                @endforeach
            </div>
        </div>
        <div class="foot-block">
            <h4 class="heading">Deadline</h4>
            <p>{{ $project['deadline'] }}</p>
        </div>
        <div class="foot-block">
            <h4 class="heading">Budget</h4>
            <p>{{ $project['budget'] }}</p>
        </div>
        <div class="foot-block">
            <h4 class="heading">Status</h4>
            <p>{{ $project['status'] }}</p>
        </div>
    </div>
</div>
