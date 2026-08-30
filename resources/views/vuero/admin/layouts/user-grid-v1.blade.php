<x-vuero.layouts.app title="User Grid 1" :script="$layoutScript">
    <section class="user-grid-page user-grid user-grid-v1" data-user-grid-v1 aria-label="User Grid 1">
        <x-vuero.layouts.user-grid-toolbar
            :filter-options="['All', 'UI/UX Design', 'Web Development', 'Software Eng.', 'Business']"
            filter-selected="All"
            cta-label="Add User"
        />

        <x-vuero.layouts.list-placeholder
            name="search"
            :large="true"
            title="We couldn't find any matching results."
            subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
            image="/vuero/images/layouts/flex-list/placeholders/search-4.svg"
            dark-image="/vuero/images/layouts/flex-list/placeholders/search-4-dark.svg"
        />

        <div class="columns is-multiline" data-user-grid-items>
            @foreach ($users as $user)
                <x-vuero.layouts.user-grid-v1-item :user="$user" />
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
