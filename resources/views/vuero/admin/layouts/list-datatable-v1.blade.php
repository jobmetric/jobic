<x-vuero.layouts.app title="Table List 1" :script="$layoutScript">
    <section class="vuero-list-layout vuero-datatable vuero-datatable-v1" data-list-datatable-v1 data-list-layout aria-label="Table List 1">
        <x-vuero.layouts.datatable-toolbar cta-label="Add User" />

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
                            <th>Name</th>
                            <th>Location</th>
                            <th>Industry</th>
                            <th>Contacts</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <x-vuero.layouts.datatable-user-row :user="$user" />
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-vuero.layouts.list-pagination />
        </div>
    </section>
</x-vuero.layouts.app>
