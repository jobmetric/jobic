import $ from '../../../admin/jquery';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { iconMarkup } from '../../../../common/icons';

const TILESETS = {
    light: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
    dark: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
};

const TILE_OPTIONS = {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
    maxZoom: 20,
    subdomains: 'abcd',
};

const iconSvg = (name, size = 13) => iconMarkup(name, size);

const normalizeSearch = (value) => String(value || '').trim().toLocaleLowerCase();

export const initializeMapsDashboard = ($dashboard, actions) => {
    const $root = $dashboard.find('[data-map-dashboard]').first();
    const mapElement = $root.find('[data-map-canvas]').get(0);

    if (!$root.length || !mapElement || mapElement.dataset.mapReady === 'true') return;

    mapElement.dataset.mapReady = 'true';

    const center = [
        Number($root.attr('data-map-center-lat')),
        Number($root.attr('data-map-center-lng')),
    ];
    const $cards = $root.find('[data-map-location]');
    const $search = $root.find('[data-map-search]');
    const $searchClear = $root.find('[data-map-search-clear]');
    const $suggestions = $root.find('[data-map-suggestions]');
    const $suggestionButtons = $root.find('[data-map-suggestion]');
    const $searchEmpty = $root.find('[data-map-search-empty]');
    const $panel = $root.find('[data-map-panel]');
    const $locationScroll = $root.find('[data-map-location-scroll]');
    const markers = [];
    let activeIndex = null;
    let activeTileLayer = null;
    let keyboardIndex = -1;

    const locations = $cards.map(function (index) {
        const $card = $(this);

        return {
            index,
            name: String($card.attr('data-location-name') || ''),
            hours: String($card.attr('data-location-hours') || ''),
            distance: String($card.attr('data-location-distance') || ''),
            phone: String($card.attr('data-location-phone') || ''),
            description: String($card.attr('data-location-description') || ''),
            lat: Number($card.attr('data-location-lat')),
            lng: Number($card.attr('data-location-lng')),
            tone: String($card.attr('data-location-tone') || 'blue'),
            mark: String($card.attr('data-location-mark') || ''),
            search: normalizeSearch(`${$card.attr('data-location-name')} ${$card.attr('data-location-description')} ${$card.attr('data-location-distance')}`),
        };
    }).get();

    const map = L.map(mapElement, {
        attributionControl: true,
        center,
        zoom: 12,
        zoomControl: false,
        minZoom: 3,
        preferCanvas: true,
        scrollWheelZoom: true,
    });

    const currentTheme = () => ($('html').attr('data-theme') === 'dark' ? 'dark' : 'light');

    const setTileTheme = (theme, preserveView = true) => {
        const mapCenter = preserveView ? map.getCenter() : L.latLng(center);
        const zoom = preserveView ? map.getZoom() : 12;
        const previousTileLayer = activeTileLayer;
        const nextTileLayer = L.tileLayer(TILESETS[theme], TILE_OPTIONS);

        activeTileLayer = nextTileLayer.addTo(map);
        map.setView(mapCenter, zoom, { animate: false });
        nextTileLayer.once('load', () => {
            if (previousTileLayer && map.hasLayer(previousTileLayer)) map.removeLayer(previousTileLayer);
        });
    };

    const popupHtml = (location) => `
        <article class="map-popup-card">
            <header class="map-popup-card__head">
                <span class="map-location-logo map-location-logo--${actions.escapeHtml(location.tone)}" aria-hidden="true">${actions.escapeHtml(location.mark)}</span>
                <span>
                    <strong>${actions.escapeHtml(location.name)}</strong>
                    <small>${actions.escapeHtml(location.hours)}</small>
                </span>
            </header>
            <p>${actions.escapeHtml(location.description)}</p>
            <footer class="map-popup-card__meta">
                <span>${iconSvg('phone')} ${actions.escapeHtml(location.phone)}</span>
                <span>${iconSvg('location')} ${actions.escapeHtml(location.distance)}</span>
            </footer>
        </article>
    `;

    const markerIcon = () => L.divIcon({
        className: 'vuero-map-marker-shell',
        html: '<span class="vuero-map-marker" aria-hidden="true"></span>',
        iconAnchor: [12, 12],
        iconSize: [24, 24],
        popupAnchor: [0, -13],
    });

    const setMarkerActive = (index) => {
        markers.forEach((marker, markerIndex) => {
            $(marker.getElement()).find('.vuero-map-marker').toggleClass('is-active', markerIndex === index);
            marker.setZIndexOffset(markerIndex === index ? 900 : 0);
        });
    };

    const revealCard = (index) => {
        const card = $cards.get(index);

        if (!card) return;

        const isDesktop = window.matchMedia('(min-width: 821px)').matches;
        if (isDesktop) {
            const scroller = $locationScroll.get(0);
            const top = card.offsetTop - 70;
            scroller?.scrollTo({ top, behavior: 'smooth' });
        } else {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    };

    const selectLocation = (index, { reveal = false, focusMap = true, announce = true } = {}) => {
        const location = locations[index];
        const marker = markers[index];

        if (!location || !marker) return;

        activeIndex = index;
        $cards.removeClass('is-selected').attr('aria-pressed', 'false');
        $cards.eq(index).addClass('is-selected').attr('aria-pressed', 'true');
        setMarkerActive(index);

        if (focusMap) {
            map.flyTo([location.lat, location.lng], 15, {
                animate: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                duration: .85,
            });
        }

        marker.bindPopup(popupHtml(location), {
            className: 'map-location-popup',
            closeButton: true,
            maxWidth: 300,
            minWidth: 250,
            offset: [0, -4],
        }).openPopup();

        if (reveal) revealCard(index);
        if (announce) actions.announce(`${location.name} selected. ${location.distance} away.`);
    };

    locations.forEach((location, index) => {
        const marker = L.marker([location.lat, location.lng], {
            alt: location.name,
            icon: markerIcon(),
            keyboard: true,
            riseOnHover: true,
            title: location.name,
        }).addTo(map);

        marker.on('click', () => selectLocation(index, { reveal: true, focusMap: true }));
        marker.on('keypress', (event) => {
            if (event.originalEvent?.key === 'Enter') selectLocation(index, { reveal: true, focusMap: true });
        });
        markers.push(marker);
    });

    setTileTheme(currentTheme(), false);

    const openSuggestions = () => {
        const hasQuery = normalizeSearch($search.val()).length > 0;

        $suggestions.prop('hidden', !hasQuery);
        $search.attr('aria-expanded', String(hasQuery));
    };

    const closeSuggestions = () => {
        keyboardIndex = -1;
        $suggestionButtons.removeClass('is-keyboard-active');
        $suggestions.prop('hidden', true);
        $search.attr('aria-expanded', 'false');
    };

    const filterLocations = (query) => {
        const normalized = normalizeSearch(query);
        let visibleCount = 0;

        locations.forEach((location, index) => {
            const visible = !normalized || location.search.includes(normalized);

            $cards.eq(index).toggleClass('is-filtered', !visible);
            $suggestionButtons.eq(index).prop('hidden', !visible);
            markers[index].setOpacity(visible ? 1 : .16);
            if (markers[index].getElement()) {
                markers[index].getElement().style.pointerEvents = visible ? '' : 'none';
            }
            if (visible) visibleCount += 1;
        });

        $root.find('[data-map-result-count]').text(visibleCount);
        $searchEmpty.prop('hidden', visibleCount !== 0);
        $searchClear.prop('hidden', !normalized);

        if (activeIndex !== null && !locations[activeIndex].search.includes(normalized)) {
            map.closePopup();
        }

        if (normalized) openSuggestions();
        else closeSuggestions();
    };

    $root
        .on('click.vueroMap', '[data-map-location]', function () {
            const index = Number($(this).attr('data-map-location'));
            selectLocation(index);

            if (window.matchMedia('(max-width: 820px)').matches) {
                mapElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        })
        .on('keydown.vueroMap', '[data-map-location]', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            $(this).trigger('click');
        })
        .on('input.vueroMap', '[data-map-search]', function () {
            keyboardIndex = -1;
            filterLocations($(this).val());
        })
        .on('focus.vueroMap', '[data-map-search]', openSuggestions)
        .on('keydown.vueroMap', '[data-map-search]', function (event) {
            const $visible = $suggestionButtons.filter(':not([hidden])');

            if (event.key === 'Escape') {
                closeSuggestions();
                this.select();
                return;
            }

            if (event.key === 'Enter' && keyboardIndex >= 0) {
                event.preventDefault();
                $visible.eq(keyboardIndex).trigger('click');
                return;
            }

            if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') return;

            event.preventDefault();
            if (!$visible.length) return;
            keyboardIndex += event.key === 'ArrowDown' ? 1 : -1;
            if (keyboardIndex >= $visible.length) keyboardIndex = 0;
            if (keyboardIndex < 0) keyboardIndex = $visible.length - 1;
            $suggestionButtons.removeClass('is-keyboard-active');
            $visible.eq(keyboardIndex).addClass('is-keyboard-active').get(0)?.scrollIntoView({ block: 'nearest' });
        })
        .on('click.vueroMap', '[data-map-search-clear]', () => {
            $search.val('').trigger('input').trigger('focus');
        })
        .on('click.vueroMap', '[data-map-suggestion]', function () {
            const index = Number($(this).attr('data-map-suggestion'));

            $search.val(locations[index].name);
            filterLocations(locations[index].name);
            closeSuggestions();
            selectLocation(index);
        })
        .on('click.vueroMap', '[data-map-zoom-in]', () => map.zoomIn())
        .on('click.vueroMap', '[data-map-zoom-out]', () => map.zoomOut())
        .on('click.vueroMap', '[data-map-reset]', () => {
            activeIndex = null;
            $cards.removeClass('is-selected').attr('aria-pressed', 'false');
            setMarkerActive(-1);
            map.closePopup();
            map.flyTo(center, 12, { duration: .75 });
            actions.announce('Map position reset.');
        })
        .on('click.vueroMap', '[data-map-list-toggle]', () => {
            $panel.get(0)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

    $(document)
        .off('pointerdown.vueroMapSearch')
        .on('pointerdown.vueroMapSearch', (event) => {
            if (!$(event.target).closest('[data-map-search-shell]').length) closeSuggestions();
        });

    map.on('popupclose', () => {
        if (activeIndex === null) return;
        $cards.eq(activeIndex).removeClass('is-selected').attr('aria-pressed', 'false');
        setMarkerActive(-1);
        activeIndex = null;
    });

    const themeObserver = new MutationObserver((mutations) => {
        if (mutations.some((mutation) => mutation.attributeName === 'data-theme')) {
            setTileTheme(currentTheme());
        }
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    let resizeFrame = null;
    const invalidateMap = () => {
        window.cancelAnimationFrame(resizeFrame);
        resizeFrame = window.requestAnimationFrame(() => map.invalidateSize({ pan: false }));
    };
    const resizeObserver = new ResizeObserver(invalidateMap);
    resizeObserver.observe(mapElement);
    $(window).on('resize.vueroMap', invalidateMap);

    map.whenReady(() => {
        invalidateMap();
        $root.addClass('is-map-ready');
    });
};
