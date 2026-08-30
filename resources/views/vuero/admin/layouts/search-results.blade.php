<x-vuero.layouts.app title="Search Results" :script="$layoutScript">
    <section class="search-results-page search-results-wrapper" data-search-results aria-label="Search Results">
        <header class="search-results-header">
            <x-vuero.forms.field>
                <x-vuero.forms.control icon="search">
                    <input class="input" type="search" placeholder="Search again..." value="design">
                </x-vuero.forms.control>
            </x-vuero.forms.field>

            <div class="search-info">
                <span>{{ $resultCount }} results found</span>
            </div>

            <div class="tabs-inner">
                <div class="tabs">
                    <ul>
                        <li class="is-active"><a href="#" data-search-tab="all"><span>All</span></a></li>
                        <li><a href="#" data-search-tab="people"><span>People</span></a></li>
                        <li><a href="#" data-search-tab="records"><span>Records</span></a></li>
                        <li class="tab-naver" aria-hidden="true"></li>
                    </ul>
                </div>
            </div>
        </header>

        <div class="search-results-body">
            <div class="columns">
                <div class="column is-4">
                    @foreach ($filters as $title => $options)
                        <div class="filter-box">
                            <h4 class="dark-inverted">{{ $title }}</h4>
                            @foreach ($options as $option)
                                <x-vuero.forms.field>
                                    <x-vuero.forms.control>
                                        <label class="checkbox is-circle">
                                            <input type="checkbox" @checked($option['checked'] ?? false)>
                                            <span>{{ $option['label'] }}</span>
                                        </label>
                                        <span class="control-end"><span class="flex-tag is-rounded">{{ $option['count'] }}</span></span>
                                    </x-vuero.forms.control>
                                </x-vuero.forms.field>
                            @endforeach
                        </div>
                    @endforeach
                </div>

                <div class="column is-8">
                    <div class="tab-content is-active" data-search-tab-panel="all">
                        <div class="search-results-group">
                            <div class="group-header">
                                <span class="icon-wrap"><x-vuero.icon name="user" size="14" /></span>
                                <h4>People</h4>
                            </div>
                            <div class="search-results-list">
                                @foreach ($people as $person)
                                    <x-vuero.layouts.search.person-item :person="$person" />
                                @endforeach
                            </div>
                        </div>

                        <div class="search-results-group">
                            <div class="group-header">
                                <span class="icon-wrap"><x-vuero.icon name="panel" size="14" /></span>
                                <h4>Records</h4>
                            </div>
                            <div class="search-results-list">
                                @foreach ($records as $record)
                                    <x-vuero.layouts.search.record-item :record="$record" />
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" data-search-tab-panel="people">
                        <div class="search-results-group">
                            <div class="search-results-list">
                                @foreach ($people as $person)
                                    <x-vuero.layouts.search.person-item :person="$person" />
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="tab-content" data-search-tab-panel="records">
                        <div class="search-results-group">
                            <div class="search-results-list">
                                @foreach ($records as $record)
                                    <x-vuero.layouts.search.record-item :record="$record" />
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
