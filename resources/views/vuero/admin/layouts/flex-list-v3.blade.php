<x-vuero.layouts.app title="Flex List 3" :script="$layoutScript">
    <section class="vuero-list-layout vuero-flex-list vuero-flex-list-v3" data-flex-list-v3 data-list-layout aria-label="Flex List 3">
        <x-vuero.layouts.list-toolbar :tabs="['all' => 'All', 'saved' => 'Saved']" active="all" reversed />

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
                :rounded="true"
                :columns="[
                    ['label' => 'Course', 'grow' => true],
                    ['label' => 'Category'],
                    ['label' => 'Main Skill'],
                    ['label' => 'Difficulty'],
                    ['label' => 'Actions', 'align' => 'end', 'class' => 'h-hidden-tablet-p'],
                ]"
            >
                @foreach ($courses as $course)
                    <x-vuero.layouts.flex-course-row :course="$course" />
                @endforeach
            </x-vuero.layouts.flex-table>

            <x-vuero.layouts.list-pagination />
        </div>

        <div class="flex-list-wrapper" data-layout-panel="saved" role="tabpanel" hidden>
            <x-vuero.layouts.list-placeholder
                name="saved"
                :large="true"
                title="No saved courses."
                subtitle="Looks like you don't have any saved courses yet. You can add a course to your saved courses by visiting the course details and clicking on the bookmark button."
                image="/vuero/images/layouts/flex-list/placeholders/courses.svg"
                dark-image="/vuero/images/layouts/flex-list/placeholders/courses-dark.svg"
            />
        </div>
    </section>
</x-vuero.layouts.app>
