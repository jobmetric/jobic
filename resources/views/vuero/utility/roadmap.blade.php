<x-vuero.layouts.minimal
    title="Utility Roadmap"
    script="resources/js/vuero/pages/utility-roadmap.js"
    body-class="cms-page cms-page--utility"
    wrapper-class="utility-minimal-wrapper"
>
    <main class="roadmap-wrapper" data-utility-roadmap>
        <header class="utility-public-head">
            <a class="utility-public-logo" href="{{ route('vuero.home') }}" aria-label="Vuero home">
                <img src="{{ asset('vuero/images/logos/logo/logo.svg') }}" alt="">
            </a>
            <nav aria-label="Utility navigation">
                <a class="action-link" href="{{ route('vuero.admin.dashboard') }}">Home</a>
                <a class="action-link" href="{{ route('vuero.utility.status') }}">App Status</a>
            </nav>
        </header>

        <div class="utility-slider-tabs" role="tablist" aria-label="Roadmap sections">
            <span class="utility-slider-naver" aria-hidden="true"></span>
            <button id="utility-tab-roadmap" class="is-active" type="button" role="tab" aria-selected="true" aria-controls="utility-panel-roadmap" tabindex="0" data-utility-tab="roadmap">Roadmap</button>
            <button id="utility-tab-changelog" type="button" role="tab" aria-selected="false" aria-controls="utility-panel-changelog" tabindex="-1" data-utility-tab="changelog">Changelog</button>
        </div>

        <section id="utility-panel-roadmap" class="roadmap-outer" role="tabpanel" aria-labelledby="utility-tab-roadmap" data-utility-panel="roadmap">
            <header class="roadmap-header">
                <h1>Our Roadmap</h1>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Omnis enim est natura diligens sui. Philosophi autem in suis lectulis plerumque moriuntur.</p>
                <div class="currently-planned">Q<span data-roadmap-quarter-label>3</span> <span data-roadmap-year-label>2022</span> Planned: <strong>22 features</strong></div>
            </header>

            <div class="roadmap-inner">
                <div class="roadmap-toolbar is-responsive">
                    <div class="roadmap-toolbar-start">
                        <label class="utility-field">
                            <span>Year</span>
                            <span class="utility-select-wrap">
                                <x-vuero.icon name="search" size="16" />
                                <select data-roadmap-year aria-label="Roadmap year">
                                    @foreach (array_keys($roadmap) as $year)
                                        <option value="{{ $year }}" @selected($year === '2022')>{{ $year }}</option>
                                    @endforeach
                                </select>
                                <x-vuero.icon name="chevron-down" size="15" />
                            </span>
                        </label>

                        <fieldset class="utility-field quarter-field">
                            <legend>Quarterly</legend>
                            <div class="quarter-buttons" role="group" aria-label="Quarter">
                                @foreach (range(1, 4) as $quarter)
                                    <button type="button" @class(['is-active' => $quarter === 3]) aria-pressed="{{ $quarter === 3 ? 'true' : 'false' }}" data-roadmap-quarter="{{ $quarter }}">Q{{ $quarter }}</button>
                                @endforeach
                            </div>
                        </fieldset>
                    </div>

                    <div class="roadmap-milestone">
                        <div><span>Year milestone</span><strong data-roadmap-year-progress>90%</strong></div>
                        <progress value="90" max="100" data-roadmap-year-progress-bar>90%</progress>
                    </div>
                </div>

                <div class="roadmap-list" data-roadmap-list>
                    @foreach ($roadmap as $year => $yearData)
                        @foreach ($yearData['quarters'] as $quarter => $features)
                            <div class="roadmap-quarter" data-roadmap-group="{{ $year }}-{{ $quarter }}" data-year-progress="{{ $yearData['progress'] }}" @if (!($year === '2022' && $quarter === 3)) hidden @endif>
                                @foreach ($features as $feature)
                                    <article class="roadmap-item">
                                        <button class="roadmap-collapse-head" type="button" aria-expanded="false">
                                            <span class="roadmap-item-title">{{ $feature['title'] }}</span>
                                            <span class="head-info"><span class="head-progress"><span>Progress</span><strong>{{ $feature['value'] }}%</strong></span><x-vuero.icon name="chevron-down" size="18" /></span>
                                            <span class="roadmap-item-progress" style="--progress: {{ $feature['value'] }}%"></span>
                                        </button>
                                        <div class="roadmap-collapse-body" hidden>
                                            <div><p>{{ $feature['content'] }}</p>@isset($feature['url'])<a class="action-link" href="{{ $feature['url'] }}" target="_blank" rel="noopener">Read More</a>@endisset</div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </section>

        <section id="utility-panel-changelog" class="roadmap-outer" role="tabpanel" aria-labelledby="utility-tab-changelog" data-utility-panel="changelog" hidden>
            <header class="roadmap-header">
                <h1>Changelog</h1>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Omnis enim est natura diligens sui. Philosophi autem in suis lectulis plerumque moriuntur.</p>
                <div class="currently-planned">Last 30 days: <strong>22 new</strong></div>
            </header>

            <div class="roadmap-inner">
                <div class="roadmap-toolbar changelog-toolbar">
                    <label class="utility-field">
                        <span>Entry types</span>
                        <span class="utility-select-wrap">
                            <x-vuero.icon name="search" size="16" />
                            <select data-changelog-filter aria-label="Changelog entry type">
                                @foreach (['All', 'Enhancements', 'Features', 'Bug fixes'] as $type)<option>{{ $type }}</option>@endforeach
                            </select>
                            <x-vuero.icon name="chevron-down" size="15" />
                        </span>
                    </label>
                </div>

                @foreach (collect($changelog)->groupBy('month') as $month => $releases)
                    <section class="changelog-month">
                        <h2>{{ $month }}</h2>
                        @foreach ($releases as $release)
                            <article class="changelog-card">
                                <header><h3>{{ $release['date'] }}</h3><span>{{ $release['tag'] }}</span></header>
                                <div>
                                    @foreach ($release['entries'] as $entry)
                                        <p class="changelog-line" data-change-type="{{ $entry['type'] }}"><span class="is-{{ str($entry['type'])->slug() }}">{{ $entry['type'] }}</span>{{ $entry['title'] }}</p>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </section>
                @endforeach
            </div>
        </section>

        <x-vuero.utility.footer />
    </main>
</x-vuero.layouts.minimal>
