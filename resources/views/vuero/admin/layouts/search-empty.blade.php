<x-vuero.layouts.app title="Empty Search" :script="$layoutScript">
    <section class="search-results-page search-results-wrapper" data-search-empty aria-label="Empty Search">
        <header class="search-results-header">
            <x-vuero.forms.field>
                <x-vuero.forms.control icon="search">
                    <input class="input" type="search" placeholder="Search again..." value="zzzzzz">
                </x-vuero.forms.control>
            </x-vuero.forms.field>

            <div class="search-info">
                <span>0 results found</span>
            </div>

            <div class="tabs-inner">
                <div class="tabs is-disabled">
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
            <section class="search-empty-placeholder is-larger">
                <div class="placeholder-image">
                    <img class="light-image" src="/vuero/images/layouts/search/placeholders/search-7.svg" alt="">
                    <img class="dark-image" src="/vuero/images/layouts/search/placeholders/search-7-dark.svg" alt="">
                </div>
                <h2>We couldn't find any matching results.</h2>
                <p>Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria.</p>
            </section>
        </div>
    </section>
</x-vuero.layouts.app>
