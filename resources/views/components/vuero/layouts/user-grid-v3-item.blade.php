@props(['user'])

<article class="column is-3" data-user-grid-item data-search="{{ Illuminate\Support\Str::lower($user['username'].' '.$user['location'].' '.$user['position'].' '.basename($user['badge'])) }}">
    <div class="grid-item" data-user-grid-tabbed-item>
        <x-vuero.layouts.flex-avatar size="big" :picture="$user['avatar']" :badge="$user['badge']" />
        <h3 class="dark-inverted">{{ $user['fullName'] }}</h3>
        <p>{{ $user['position'] }}</p>

        <div class="icon-tabs-wrapper">
            <div class="icon-tabs">
                <div class="icon-tabs-inner">
                    <button type="button" class="tab-item is-active" data-icon-tab="posts" aria-pressed="true">
                        <x-vuero.icon name="activity" size="16" />
                    </button>
                    <button type="button" class="tab-item" data-icon-tab="projects" aria-pressed="false">
                        <x-vuero.icon name="briefcase" size="16" />
                    </button>
                    <button type="button" class="tab-item" data-icon-tab="tasks" aria-pressed="false">
                        <x-vuero.icon name="check-circle" size="16" />
                    </button>
                    <div class="icon-tabs-naver" aria-hidden="true"></div>
                </div>
            </div>

            <div class="icon-tabs-content is-active" data-icon-tab-panel="posts">
                <div class="chart-block">
                    <svg class="user-grid-sparkline" width="100" height="45" viewBox="0 0 100 45" data-user-grid-sparkline aria-hidden="true"></svg>
                    <div class="stats">
                        <span class="dark-inverted" data-user-grid-sparkline-value>{{ $user['posts']['count'] }}</span>
                        <span>Viewers</span>
                    </div>
                </div>
            </div>

            <div class="icon-tabs-content" data-icon-tab-panel="projects" hidden>
                <div class="chart-block">
                    <div class="circle-chart-wrapper is-info">
                        <x-vuero.layouts.user-grid-circle-chart :value="$user['projects']['progress']" tone="info" />
                    </div>
                    <div class="stats">
                        <span class="dark-inverted">{{ $user['projects']['count'] }}</span>
                        <span>Projects</span>
                    </div>
                </div>
            </div>

            <div class="icon-tabs-content" data-icon-tab-panel="tasks" hidden>
                <div class="chart-block">
                    <div class="circle-chart-wrapper is-warning">
                        <x-vuero.layouts.user-grid-circle-chart :value="$user['tasks']['progress']" tone="warning" />
                    </div>
                    <div class="stats">
                        <span class="dark-inverted">{{ $user['tasks']['count'] }}</span>
                        <span>Tasks</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="buttons">
            <button type="button" class="button v-button is-dark-outlined" data-user-grid-action="hire" data-user-grid-label="{{ $user['fullName'] }}">
                <span class="icon"><x-vuero.icon name="check" size="16" /></span>
                <span>Hire</span>
            </button>
            <button type="button" class="button v-button is-dark-outlined" data-user-grid-action="talk" data-user-grid-label="{{ $user['fullName'] }}">
                <span class="icon"><x-vuero.icon name="message" size="16" /></span>
                <span>Talk</span>
            </button>
        </div>
    </div>
</article>
