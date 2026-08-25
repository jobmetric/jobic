@props(['locations'])

<section class="map-dashboard__canvas" aria-label="Interactive map of recent locations">
    <div class="map-dashboard__map" data-map-canvas></div>

    <div class="map-search" data-map-search-shell>
        <label class="map-search__field" for="map-location-search">
            <x-vuero.icon name="search" size="20" />
            <span class="sr-only">Search locations</span>
            <input
                id="map-location-search"
                type="search"
                placeholder="Search a location..."
                autocomplete="off"
                aria-autocomplete="list"
                aria-controls="map-search-suggestions"
                aria-expanded="false"
                data-map-search
            >
            <button type="button" aria-label="Clear location search" data-map-search-clear hidden>
                <x-vuero.icon name="x" size="16" />
            </button>
        </label>

        <div class="map-search__suggestions" id="map-search-suggestions" role="listbox" data-map-suggestions hidden>
            @foreach ($locations as $index => $location)
                <button type="button" role="option" data-map-suggestion="{{ $index }}">
                    <span class="map-location-logo map-location-logo--{{ $location['tone'] }}" aria-hidden="true">{{ $location['mark'] }}</span>
                    <span><strong>{{ $location['name'] }}</strong><small>{{ $location['distance'] }} away</small></span>
                    <x-vuero.icon name="chevron-right" size="16" />
                </button>
            @endforeach
            <p class="map-search__empty" data-map-search-empty hidden>No location matches your search.</p>
        </div>
    </div>

    <div class="map-controls" aria-label="Map controls">
        <button type="button" aria-label="Zoom in" title="Zoom in" data-map-zoom-in>
            <x-vuero.icon name="plus" size="18" />
        </button>
        <button type="button" aria-label="Zoom out" title="Zoom out" data-map-zoom-out>
            <span aria-hidden="true">−</span>
        </button>
        <button type="button" aria-label="Reset map position" title="Reset map" data-map-reset>
            <x-vuero.icon name="location" size="18" />
        </button>
    </div>

    <button class="map-dashboard__mobile-list" type="button" data-map-list-toggle>
        <x-vuero.icon name="apps" size="17" />
        View locations
    </button>
</section>
