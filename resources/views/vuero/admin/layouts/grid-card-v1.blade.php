<x-vuero.layouts.app title="Grid Cards 1" :script="$layoutScript">
    <section class="card-grid-page card-grid card-grid-v1" data-grid-card-v1 aria-label="Grid Cards 1">
        <x-vuero.layouts.card-grid-toolbar
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

        <div class="columns is-multiline" data-grid-items>
            @foreach ($users as $user)
                <x-vuero.layouts.grid-card-v1-item :user="$user" />
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
