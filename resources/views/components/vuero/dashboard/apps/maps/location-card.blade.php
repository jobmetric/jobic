@props(['location', 'index'])

<article
    class="map-location-card"
    tabindex="0"
    role="button"
    aria-label="Show {{ $location['name'] }} on the map"
    aria-pressed="false"
    data-map-location="{{ $index }}"
    data-location-name="{{ $location['name'] }}"
    data-location-hours="{{ $location['hours'] }}"
    data-location-distance="{{ $location['distance'] }}"
    data-location-phone="{{ $location['phone'] }}"
    data-location-description="{{ $location['description'] }}"
    data-location-lat="{{ $location['coordinates'][1] }}"
    data-location-lng="{{ $location['coordinates'][0] }}"
    data-location-tone="{{ $location['tone'] }}"
    data-location-mark="{{ $location['mark'] }}"
>
    <header class="map-location-card__header">
        <span class="map-location-logo map-location-logo--{{ $location['tone'] }}" aria-hidden="true">{{ $location['mark'] }}</span>
        <span class="map-location-card__identity">
            <strong>{{ $location['name'] }}</strong>
            <small><span class="map-location-card__status" aria-hidden="true"></span>{{ $location['hours'] }}</small>
        </span>
        <x-vuero.icon class="map-location-card__arrow" name="chevron-right" size="18" />
    </header>

    <p>{{ $location['description'] }}</p>

    <footer class="map-location-card__footer">
        <span class="map-location-card__rating" aria-label="Rated 5 out of 5">
            @for ($star = 0; $star < 5; $star++)
                <x-vuero.icon name="star" size="13" />
            @endfor
        </span>
        <span class="map-location-card__distance">
            <x-vuero.icon name="location" size="14" />
            {{ $location['distance'] }}
        </span>
    </footer>
</article>
