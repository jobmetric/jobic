<x-vuero.layouts.app title="Flex List 2" :script="$layoutScript">
    <section class="vuero-list-layout vuero-flex-list vuero-flex-list-v2" data-flex-list-v2 data-list-layout aria-label="Flex List 2">
        <x-vuero.layouts.list-toolbar :tabs="['active' => 'Active', 'closed' => 'Closed']" active="active" reversed />

        <div class="flex-list-wrapper" data-layout-panel="active">
            <x-vuero.layouts.list-placeholder
                name="search"
                :large="true"
                title="We couldn't find any matching results."
                subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
                image="/vuero/images/layouts/flex-list/placeholders/search-4.svg"
                dark-image="/vuero/images/layouts/flex-list/placeholders/search-4-dark.svg"
            />

            <x-vuero.layouts.flex-table
                :rounded="true"
                :columns="[
                    ['label' => 'Project', 'grow' => true],
                    ['label' => 'Customer'],
                    ['label' => 'Industry'],
                    ['label' => 'Status'],
                    ['label' => 'Team', 'class' => 'h-hidden-tablet-p'],
                    ['label' => 'Actions', 'align' => 'end'],
                ]"
            >
                @foreach ($projects as $project)
                    <x-vuero.layouts.flex-project-row :project="$project" />
                @endforeach
            </x-vuero.layouts.flex-table>

            <x-vuero.layouts.list-pagination />
        </div>

        <div class="flex-list-wrapper" data-layout-panel="closed" role="tabpanel" hidden>
            <x-vuero.layouts.list-placeholder
                name="closed"
                :large="true"
                title="No closed projects."
                subtitle="Looks like you don't have any closed project yet. When you'll start closing off projects, they will be showing up in here."
                image="/vuero/images/layouts/flex-list/placeholders/projects.svg"
                dark-image="/vuero/images/layouts/flex-list/placeholders/projects-dark.svg"
            />
        </div>
    </section>
</x-vuero.layouts.app>
