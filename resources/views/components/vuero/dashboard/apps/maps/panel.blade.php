@props(['locations'])

<aside class="map-dashboard__panel" data-map-panel aria-label="Recent locations">
    <header class="map-dashboard__header">
        <h1>Locations</h1>

        <div class="map-dashboard__toolbar">
            <button class="toolbar-button theme-toggle" type="button" data-theme-toggle aria-label="Toggle dark mode" aria-pressed="false">
                <x-vuero.icon class="theme-toggle__sun" name="sun" size="22" />
                <x-vuero.icon class="theme-toggle__moon" name="moon" size="22" />
            </button>
            <button class="toolbar-button language-trigger" type="button" data-panel-open="languages" aria-label="Select language" aria-controls="languages-panel" aria-expanded="false">
                <span class="country-flag country-flag--us" data-language-current-flag aria-hidden="true"></span>
            </button>
            <x-vuero.navigation.notifications-dropdown placement="map" />
        </div>
    </header>

    <div class="map-dashboard__locations" data-map-location-scroll>
        <div class="map-dashboard__section-title">
            <span>RECENT LOCATIONS</span>
            <strong><span data-map-result-count>{{ count($locations) }}</span> places</strong>
        </div>

        <div class="map-location-list" data-map-location-list>
            @foreach ($locations as $index => $location)
                <x-vuero.dashboard.apps.maps.location-card :location="$location" :index="$index" />
            @endforeach
        </div>
    </div>
</aside>
