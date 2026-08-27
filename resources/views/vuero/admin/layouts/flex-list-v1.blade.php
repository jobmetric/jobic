<x-vuero.layouts.app title="Flex List 1" :script="$layoutScript">
    <section class="vuero-list-layout vuero-flex-list vuero-flex-list-v1" data-flex-list-v1 data-list-layout aria-label="Flex List 1">
        <x-vuero.layouts.flex-list-toolbar />

        <div class="flex-list-wrapper" data-layout-panel="all">
            <x-vuero.layouts.list-placeholder
                name="search"
                :large="true"
                title="We couldn't find any matching results."
                subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
                image="/vuero/images/layouts/flex-list/placeholders/search-4.svg"
                dark-image="/vuero/images/layouts/flex-list/placeholders/search-4-dark.svg"
            />

            <x-vuero.layouts.flex-table
                :compact="true"
                :columns="[
                    ['label' => 'User', 'grow' => true],
                    ['label' => 'Location'],
                    ['label' => 'Industry'],
                    ['label' => 'Status'],
                    ['label' => 'Relations'],
                    ['label' => 'Actions', 'align' => 'end'],
                ]"
            >
                @foreach ($users as $user)
                    <x-vuero.layouts.flex-user-row :user="$user" />
                @endforeach
            </x-vuero.layouts.flex-table>

            <x-vuero.layouts.list-pagination />
        </div>
    </section>
</x-vuero.layouts.app>
