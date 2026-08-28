<x-vuero.layouts.app title="Grid Tiles 2" :script="$layoutScript">
    <section class="tile-grid-page tile-grid tile-grid-v2" data-grid-tile-v2 aria-label="Grid Tiles 2">
        <x-vuero.layouts.tile-grid-toolbar
            :filter-options="['All Files', 'Recently Updated', 'My Files', 'Team Files', 'Deprecated']"
            filter-selected="All Files"
            cta-label="Add File"
        />

        <x-vuero.layouts.list-placeholder
            name="search"
            :large="true"
            title="We couldn't find any matching results."
            subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
            image="/vuero/images/layouts/flex-list/placeholders/search-4.svg"
            dark-image="/vuero/images/layouts/flex-list/placeholders/search-4-dark.svg"
        />

        <div class="columns is-multiline" data-tile-items>
            @foreach ($files as $file)
                <x-vuero.layouts.tile-grid-v2-item :file="$file" />
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
