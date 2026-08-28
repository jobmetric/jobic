<x-vuero.layouts.app title="Grid Cards 3" :script="$layoutScript">
    <section class="card-grid-page card-grid card-grid-v3" data-grid-card-v3 aria-label="Grid Cards 3">
        <x-vuero.layouts.card-grid-toolbar
            :filter-options="['All Projects', 'Web Apps', 'Mobile Apps', 'Dashboards', 'Landing Pages']"
            filter-selected="All Projects"
            cta-label="New Project"
        />

        <x-vuero.layouts.list-placeholder
            name="search"
            :large="true"
            title="We couldn't find any matching results."
            subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
            image="/vuero/images/layouts/list-view-v3/placeholders/search-3.svg"
            dark-image="/vuero/images/layouts/list-view-v3/placeholders/search-3-dark.svg"
        />

        <div class="columns is-multiline is-flex-tablet-p is-half-tablet-p" data-grid-items>
            @foreach ($projects as $project)
                <x-vuero.layouts.grid-card-v3-item :project="$project" />
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
