<x-vuero.layouts.app title="List View 1" :script="$layoutScript">
    <section class="vuero-list-view-v1" data-list-view-v1 aria-label="List View 1">
        <header class="list-view-toolbar">
            <label class="list-view-search">
                <span class="sr-only">Search users</span>
                <x-vuero.icon name="search" size="17" />
                <input type="search" placeholder="Search..." autocomplete="off" data-list-filter>
                <button type="button" aria-label="Clear search" data-list-filter-clear hidden><x-vuero.icon name="x" size="15" /></button>
            </label>
            <p class="list-view-count" aria-live="polite"><span data-list-count>{{ count($users) }}</span> <span data-list-count-label>records found</span></p>
            <button class="list-view-approve" type="button" data-list-approve><x-vuero.icon name="check" size="15" /><span>Approve</span></button>
        </header>

        <div class="list-view-empty" data-list-empty hidden>
            <img class="is-light" src="/vuero/images/layouts/list-view-v1/placeholders/search-1.svg" alt="">
            <img class="is-dark" src="/vuero/images/layouts/list-view-v1/placeholders/search-1-dark.svg" alt="">
            <h2>We couldn't find any matching results.</h2>
            <p>Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria.</p>
        </div>

        <div class="list-view-items" data-list-items>
            @foreach ($users as $index => $user)
                <x-vuero.layouts.list-view-user :user="$user" :index="$index" />
            @endforeach
        </div>

        <nav class="list-view-pagination" aria-label="Pagination" data-list-pagination>
            <button type="button" class="pagination-edge" aria-label="Previous page" data-page="41"><x-vuero.icon name="chevron-left" size="16" /></button>
            <ol>
                @foreach ([1, null, 40, 41, 42, 43, 44, null, 88] as $page)
                    <li>
                        @if (is_null($page))
                            <span class="pagination-ellipsis">…</span>
                        @else
                            <button type="button" class="{{ $page === 42 ? 'is-current' : '' }}" data-page="{{ $page }}" @if ($page === 42) aria-current="page" @endif>{{ $page }}</button>
                        @endif
                    </li>
                @endforeach
            </ol>
            <button type="button" class="pagination-edge" aria-label="Next page" data-page="43"><x-vuero.icon name="chevron-right" size="16" /></button>
        </nav>
    </section>
</x-vuero.layouts.app>
