<x-vuero.layouts.app title="Table List 2" :script="$layoutScript">
    <section class="vuero-list-layout vuero-datatable vuero-datatable-v2" data-list-datatable-v2 data-list-layout aria-label="Table List 2">
        <x-vuero.layouts.datatable-toolbar cta-label="New Project" />

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
                            <th>Project</th>
                            <th>Customer</th>
                            <th>Industry</th>
                            <th>Status</th>
                            <th>Team</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($projects as $project)
                            <x-vuero.layouts.datatable-project-row :project="$project" />
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-vuero.layouts.list-pagination />
        </div>
    </section>
</x-vuero.layouts.app>
