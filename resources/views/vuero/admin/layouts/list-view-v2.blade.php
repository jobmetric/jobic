<x-vuero.layouts.app title="List View 2" :script="$layoutScript">
    <section class="vuero-list-layout vuero-list-view-v2" data-list-view-v2 data-list-layout aria-label="List View 2">
        <x-vuero.layouts.list-toolbar :tabs="['active' => 'Active', 'inactive' => 'Inactive']" active="active" />

        <div data-layout-panel="active" role="tabpanel">
            <x-vuero.layouts.list-placeholder
                name="search"
                title="We couldn't find any matching results."
                subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
                image="/vuero/images/layouts/list-view-v2/placeholders/search-2.svg"
                dark-image="/vuero/images/layouts/list-view-v2/placeholders/search-2-dark.svg"
            />
            <div class="property-list" data-layout-items>
                @foreach ($properties as $index => $property)<x-vuero.layouts.property-card :property="$property" :index="$index" />@endforeach
            </div>
            <x-vuero.layouts.list-pagination />
        </div>

        <div data-layout-panel="inactive" role="tabpanel" hidden>
            <x-vuero.layouts.list-placeholder
                name="inactive"
                title="There are no inactive properties."
                subtitle="Looks like there are no inactive properties to display. You can disable and also enable a property from the property settings."
                image="/vuero/images/layouts/list-view-v2/placeholders/having-coffee.svg"
                dark-image="/vuero/images/layouts/list-view-v2/placeholders/having-coffee-dark.svg"
            />
        </div>
    </section>
</x-vuero.layouts.app>
