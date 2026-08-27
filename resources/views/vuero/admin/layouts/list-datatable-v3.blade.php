<x-vuero.layouts.app title="Table List 3" :script="$layoutScript">
    <section class="vuero-list-layout vuero-datatable vuero-datatable-v3" data-list-datatable-v3 data-list-layout aria-label="Table List 3">
        <x-vuero.layouts.datatable-toolbar cta-label="New Offer" />

        <div class="datatable-wrapper" data-layout-panel="all">
            <x-vuero.layouts.list-placeholder
                name="search"
                :large="true"
                title="We couldn't find any matching results."
                subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
                image="/vuero/images/layouts/list-datatable/placeholders/search-7.svg"
                dark-image="/vuero/images/layouts/list-datatable/placeholders/search-7-dark.svg"
            />

            <div class="table-container" data-layout-table>
                <table class="table datatable-table is-fullwidth">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Offer</th>
                            <th>Location</th>
                            <th>Duration</th>
                            <th>Requirements</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($offers as $offer)
                            <x-vuero.layouts.datatable-offer-row :offer="$offer" />
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-vuero.layouts.list-pagination />
        </div>
    </section>
</x-vuero.layouts.app>
