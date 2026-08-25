@props([
    'variant' => 'right',
])

@php
    $locations = [
        [
            'name' => 'Fast Pizza',
            'hours' => 'Open until 6pm',
            'distance' => '0.3 mile',
            'coordinates' => [-77.038659, 38.931567],
            'phone' => '+1 555 456-5659',
            'tone' => 'blue',
            'mark' => 'P',
            'description' => 'Authentic wood-fired pizza, handmade pasta, and generous Italian classics.',
        ],
        [
            'name' => 'Envato',
            'hours' => 'Open until 5pm',
            'distance' => '0.8 mile',
            'coordinates' => [-77.003168, 38.894651],
            'phone' => '+1 555 154-4568',
            'tone' => 'green',
            'mark' => 'E',
            'description' => 'A creative workplace for digital makers, designers, and independent teams.',
        ],
        [
            'name' => 'Lipflow',
            'hours' => 'Open until 8pm',
            'distance' => '1.2 miles',
            'coordinates' => [-77.090372, 38.881189],
            'phone' => '+1 555 456-7897',
            'tone' => 'red',
            'mark' => 'L',
            'description' => 'Contemporary beauty essentials, expert advice, and a relaxed studio experience.',
        ],
        [
            'name' => 'Metamovies',
            'hours' => 'Open until 11pm',
            'distance' => '0.5 mile',
            'coordinates' => [-77.111561, 38.882342],
            'phone' => '+1 555 321-7745',
            'tone' => 'orange',
            'mark' => 'M',
            'description' => 'Independent films, timeless favourites, and late screenings in a boutique cinema.',
        ],
        [
            'name' => 'Slicer',
            'hours' => 'Open until 4:30pm',
            'distance' => '0.2 mile',
            'coordinates' => [-77.052477, 38.943951],
            'phone' => '+1 555 424-9632',
            'tone' => 'purple',
            'mark' => 'S',
            'description' => 'Fresh sandwiches and seasonal bowls prepared quickly from local ingredients.',
        ],
        [
            'name' => 'Drop',
            'hours' => 'Open until 7pm',
            'distance' => '2.1 miles',
            'coordinates' => [-77.043444, 38.909664],
            'phone' => '+1 555 615-1280',
            'tone' => 'pink',
            'mark' => 'D',
            'description' => 'A neighbourhood coffee bar serving careful brews and freshly baked pastries.',
        ],
        [
            'name' => 'Fresco',
            'hours' => 'Open until 6pm',
            'distance' => '0.6 mile',
            'coordinates' => [-77.031706, 38.914581],
            'phone' => '+1 555 903-4221',
            'tone' => 'lime',
            'mark' => 'F',
            'description' => 'A bright market for organic produce, artisan goods, and everyday essentials.',
        ],
        [
            'name' => 'Hairz',
            'hours' => 'Open until 6pm',
            'distance' => '0.3 mile',
            'coordinates' => [-77.020945, 38.878241],
            'phone' => '+1 555 109-5824',
            'tone' => 'dark',
            'mark' => 'H',
            'description' => 'Modern cuts, colour, and styling from a friendly team of independent artists.',
        ],
        [
            'name' => 'Vego LLC',
            'hours' => 'Open until 9pm',
            'distance' => '0.3 mile',
            'coordinates' => [-77.007481, 38.876516],
            'phone' => '+1 555 708-2387',
            'tone' => 'teal',
            'mark' => 'V',
            'description' => 'Plant-led comfort food with colourful ingredients and plenty of flavour.',
        ],
    ];
@endphp

<div
    class="map-dashboard map-dashboard--{{ $variant }}"
    data-map-dashboard
    data-map-layout="{{ $variant }}"
    data-map-center-lat="38.9072"
    data-map-center-lng="-77.0369"
>
    <x-vuero.dashboard.apps.maps.canvas :locations="$locations" />
    <x-vuero.dashboard.apps.maps.panel :locations="$locations" />
</div>
