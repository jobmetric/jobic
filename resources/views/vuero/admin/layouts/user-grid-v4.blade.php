<x-vuero.layouts.app title="User Grid 4" :script="$layoutScript">
    <section class="user-grid-page user-grid user-grid-v4" data-user-grid-v4 aria-label="User Grid 4">
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
            image="/vuero/images/layouts/user-grids/placeholders/search-5.svg"
            dark-image="/vuero/images/layouts/user-grids/placeholders/search-5-dark.svg"
        />

        <div class="columns is-multiline is-flex-tablet-p is-half-tablet-p" data-user-grid-items>
            @foreach ($users as $user)
                <x-vuero.layouts.user-grid-v4-item :user="$user" />
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
