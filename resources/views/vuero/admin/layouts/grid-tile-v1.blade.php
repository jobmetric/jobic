<x-vuero.layouts.app title="Grid Tiles 1" :script="$layoutScript">
    <section class="tile-grid-page tile-grid tile-grid-v1" data-grid-tile-v1 aria-label="Grid Tiles 1">
        <x-vuero.layouts.tile-grid-toolbar
            :filter-options="['All', 'UI/UX Design', 'Web Development', 'Software Eng.', 'Business']"
            filter-selected="All"
            cta-label="Add User"
        />

        <x-vuero.layouts.list-placeholder
            name="search"
            :large="true"
            title="We couldn't find any matching results."
            subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
            image="/vuero/images/layouts/grid-tiles/placeholders/search-6.svg"
            dark-image="/vuero/images/layouts/grid-tiles/placeholders/search-6-dark.svg"
        />

        <div class="columns is-multiline" data-tile-items>
            @foreach ($users as $user)
                <x-vuero.layouts.tile-grid-v1-item :user="$user" />
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
