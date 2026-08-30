<x-vuero.layouts.app title="User Grid 3" :script="$layoutScript">
    <section class="user-grid-page user-grid user-grid-v3" data-user-grid-v3 aria-label="User Grid 3">
        <x-vuero.layouts.user-grid-toolbar variant="tabs" />

        <x-vuero.layouts.list-placeholder
            name="search"
            :large="true"
            title="We couldn't find any matching results."
            subtitle="Too bad. Looks like we couldn't find any matching results for the search terms you've entered. Please try different search terms or criteria."
            image="/vuero/images/layouts/user-grids/placeholders/search-5.svg"
            dark-image="/vuero/images/layouts/user-grids/placeholders/search-5-dark.svg"
        />

        <div class="tab-content is-active" data-user-grid-tab-panel="all">
            <div class="columns is-multiline is-flex-tablet-p is-half-tablet-p" data-user-grid-items>
                @foreach ($users as $user)
                    <x-vuero.layouts.user-grid-v3-item :user="$user" />
                @endforeach
            </div>
        </div>

        <div class="tab-content" data-user-grid-tab-panel="team" hidden>
            <x-vuero.layouts.list-placeholder
                name="team"
                title="No team members."
                subtitle="Looks like you don't have any team members yet. When you'll start adding some to your team, they will be showing up in here."
                image="/vuero/images/layouts/user-grids/placeholders/team.svg"
                dark-image="/vuero/images/layouts/user-grids/placeholders/team-dark.svg"
            />
        </div>
    </section>
</x-vuero.layouts.app>
