<x-vuero.layouts.app title="Grid Cards 4" :script="$layoutScript">
    <section class="card-grid-page card-grid card-grid-v4" data-grid-card-v4 aria-label="Grid Cards 4">
        <x-vuero.layouts.card-grid-toolbar
            :filter-options="['All Posts', 'Recent Posts', 'Older Posts', 'Popular Posts']"
            filter-selected="All Posts"
            cta-label="New Post"
        />

        <x-vuero.layouts.list-placeholder
            name="search"
            :large="true"
            title="We couldn't find any matching results."
            subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
            image="/vuero/images/layouts/flex-list/placeholders/search-4.svg"
            dark-image="/vuero/images/layouts/flex-list/placeholders/search-4-dark.svg"
        />

        <div class="columns is-multiline" data-grid-items>
            @foreach ($posts as $post)
                <x-vuero.layouts.grid-card-v4-item :post="$post" />
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
