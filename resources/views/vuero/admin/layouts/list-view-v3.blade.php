<x-vuero.layouts.app title="List View 3" :script="$layoutScript">
    <section class="vuero-list-layout vuero-list-view-v3" data-list-view-v3 data-list-layout aria-label="List View 3">
        <x-vuero.layouts.list-toolbar :tabs="['all' => 'All', 'saved' => 'Saved']" active="all" reversed />

        <div data-layout-panel="all" role="tabpanel">
            <x-vuero.layouts.list-placeholder
                name="search"
                title="We couldn't find any matching results."
                subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
                image="/vuero/images/layouts/list-view-v3/placeholders/search-3.svg"
                dark-image="/vuero/images/layouts/list-view-v3/placeholders/search-3-dark.svg"
            />
            <div class="job-list" data-layout-items>
                @foreach ($jobs as $index => $job)<x-vuero.layouts.job-card :job="$job" :index="$index" />@endforeach
            </div>
            <x-vuero.layouts.list-pagination />
        </div>

        <div data-layout-panel="saved" role="tabpanel" hidden>
            <div class="job-list job-list--saved" data-saved-items></div>
            <x-vuero.layouts.list-placeholder
                name="saved"
                title="No saved jobs."
                subtitle="Looks like you don't have any saved jobs for the moment. It's also possible that some of your saved items expired."
                image="/vuero/images/layouts/list-view-v3/placeholders/thinking-canvas.svg"
                dark-image="/vuero/images/layouts/list-view-v3/placeholders/thinking-canvas-dark.svg"
            />
        </div>
    </section>
</x-vuero.layouts.app>
