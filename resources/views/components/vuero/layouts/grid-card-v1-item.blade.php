@props(['user'])

<article class="column is-6" data-grid-item data-search="{{ Illuminate\Support\Str::lower($user['name'].' '.$user['role'].' '.basename($user['badge'])) }}">
    <div class="card-grid-item">
        <div class="card-grid-item-body">
            <div class="left">
                <x-vuero.layouts.flex-avatar size="big" :picture="$user['avatar']" :badge="$user['badge']" />
                <div class="meta">
                    <span class="dark-inverted">{{ $user['name'] }}</span>
                    <span>{{ $user['role'] }}</span>
                </div>
            </div>
            <div class="right">
                <div class="social-links">
                    @foreach ($user['social'] as $channel)
                        <a href="{{ $channel['url'] }}" class="social-link" target="_blank" rel="noopener noreferrer" aria-label="{{ $channel['channel'] }}">
                            <x-vuero.icon :name="$channel['icon']" size="12" />
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card-grid-item-footer">
            <div class="left">
                <div class="progress-stats">
                    <span class="dark-inverted">Progress</span>
                    <span>{{ $user['progress'] }}%</span>
                </div>
                <div class="progress progress-tiny">
                    <progress max="100" value="{{ $user['progress'] }}"></progress>
                </div>
            </div>
            <div class="right">
                <button type="button" class="button v-button is-dark-outlined is-rounded" data-grid-action="profile" data-grid-label="{{ $user['name'] }}">View Profile</button>
            </div>
        </div>
    </div>
</article>
