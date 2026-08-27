<x-vuero.layouts.app title="List View 4" :script="$layoutScript">
    <section class="vuero-list-layout vuero-list-view-v4" data-list-view-v4 data-list-layout aria-label="List View 4">
        <x-vuero.layouts.list-toolbar :tabs="['all' => 'All', 'saved' => 'Saved']" active="all" reversed />

        <div data-layout-panel="all" role="tabpanel">
            <x-vuero.layouts.list-placeholder
                name="search"
                title="We couldn't find any matching results."
                subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
                image="/vuero/images/layouts/list-view-v3/placeholders/search-3.svg"
                dark-image="/vuero/images/layouts/list-view-v3/placeholders/search-3-dark.svg"
            />
            <div class="list-view-inner" data-layout-items>
                @foreach ($recipes as $index => $recipe)
                    <x-vuero.layouts.recipe-card :recipe="$recipe" :index="$index" />
                @endforeach
            </div>
            <x-vuero.layouts.list-pagination />
        </div>

        <div data-layout-panel="saved" role="tabpanel" hidden>
            <div class="list-view-inner" data-saved-items></div>
            <x-vuero.layouts.list-placeholder
                name="saved"
                title="No saved recipes."
                subtitle="Looks like you don't have any saved recipes for the moment. Start by exploring the latest ones and add your favorites to the saved recipes section."
                image="/vuero/images/layouts/list-view-v4/placeholders/cooking.svg"
                dark-image="/vuero/images/layouts/list-view-v4/placeholders/cooking-dark.svg"
            />
        </div>
    </section>
</x-vuero.layouts.app>
