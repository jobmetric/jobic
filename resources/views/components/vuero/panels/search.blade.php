<section class="side-panel-layer" id="search-panel" data-side-panel="search" aria-hidden="true">
    <button class="side-panel-layer__overlay" type="button" tabindex="-1" data-panel-close aria-label="Close search panel"></button>

    <aside class="side-panel side-panel--left search-panel" role="dialog" aria-modal="true" aria-label="Search" tabindex="-1">
        <header class="side-panel__header search-panel__header">
            <x-vuero.brand compact />
            <button class="side-panel__close" type="button" data-panel-close aria-label="Close search panel">
                <x-vuero.icon name="chevron-left" size="20" />
            </button>
        </header>

        <div class="search-panel__body">
            <label class="search-panel__field">
                <x-vuero.icon name="search" size="19" />
                <input type="search" placeholder="Search..." aria-label="Search the admin area" autocomplete="off" data-global-search>
            </label>

            <div class="search-results" data-search-results hidden>
                <h3>Search Results</h3>
                <div class="search-block">
                    @foreach ([
                        ['Alice C.', 'Software Engineer', 'AC', 'blue'],
                        ['Tara S.', 'UI/UX Designer', 'TS', 'purple'],
                        ['Jimmy H.', 'Project Manager', 'JH', 'orange'],
                        ['Erik K.', 'Product Manager', 'EK', 'green'],
                    ] as [$name, $position, $initials, $tone])
                        <button class="search-entry" type="button" data-search-item data-search-text="{{ strtolower($name.' '.$position) }}">
                            <span class="activity-avatar activity-avatar--{{ $tone }}">{{ $initials }}</span>
                            <span><strong>{{ $name }}</strong><small>{{ $position }}</small></span>
                        </button>
                    @endforeach
                    <p class="search-empty" data-search-empty hidden>No matching results found.</p>
                </div>
            </div>

            <section class="search-recent" data-search-default>
                <h3>Recently viewed</h3>
                <div class="search-block">
                    <a class="search-entry" href="#"><span class="search-entry__icon is-blue"><x-vuero.icon name="chrome" size="18" /></span><span><strong>Browser Support</strong><small>Blog post</small></span></a>
                    <a class="search-entry" href="#"><span class="search-entry__icon is-orange"><x-vuero.icon name="tv" size="18" /></span><span><strong>Twitch API</strong><small>Blog post</small></span></a>
                    <a class="search-entry" href="#"><span class="search-entry__icon is-green"><x-vuero.icon name="twitter" size="18" /></span><span><strong>Twitter API</strong><small>Blog post</small></span></a>
                </div>
            </section>

            <section class="search-recent" data-search-default>
                <h3>Recent Members</h3>
                <div class="search-block">
                    <a class="search-entry" href="#"><span class="activity-avatar activity-avatar--blue">AC</span><span><strong>Alice C.</strong><small>Software Engineer</small></span></a>
                    <a class="search-entry" href="#"><span class="activity-avatar activity-avatar--purple">TS</span><span><strong>Tara S.</strong><small>UI/UX Designer</small></span></a>
                    <a class="search-entry" href="#"><span class="activity-avatar activity-avatar--orange">JH</span><span><strong>Jimmy H.</strong><small>Project Manager</small></span></a>
                </div>
            </section>
        </div>
    </aside>
</section>
