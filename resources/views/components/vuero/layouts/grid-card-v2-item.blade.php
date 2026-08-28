@props(['project'])

<article class="column is-4" data-grid-item data-search="{{ Illuminate\Support\Str::lower($project['name'].' '.$project['dueDate'].' '.$project['owner']['name']) }}">
    <div class="card-grid-item">
        <div class="card">
            <header class="card-header">
                <div class="card-header-title">
                    <x-vuero.layouts.flex-avatar size="small" :picture="$project['owner']['avatar']" />
                    <div class="meta">
                        <span class="dark-inverted">{{ $project['owner']['name'] }}</span>
                        <span>updated {{ $project['updated'] }}</span>
                    </div>
                </div>
                <x-vuero.layouts.user-card-dropdown />
            </header>
            <div class="card-image">
                <figure class="image is-16by9">
                    <img src="{{ $project['image'] }}" alt="" loading="lazy">
                </figure>
            </div>
            <div class="card-content">
                <div class="card-content-flex">
                    <div class="card-info">
                        <h3 class="dark-inverted">{{ $project['name'] }}</h3>
                        <p><x-vuero.icon name="calendar" size="14" /><span>{{ $project['dueDate'] }}</span></p>
                    </div>
                    <x-vuero.layouts.flex-avatar-stack :avatars="$project['team']" :limit="3" />
                </div>
            </div>
            <footer class="card-footer">
                <button type="button" class="card-footer-item" data-grid-action="view" data-grid-label="{{ $project['name'] }}">View</button>
                <button type="button" class="card-footer-item" data-grid-action="settings" data-grid-label="{{ $project['name'] }}">Settings</button>
            </footer>
        </div>
    </div>
</article>
