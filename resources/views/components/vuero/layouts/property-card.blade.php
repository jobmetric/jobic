@props(['property', 'index'])

@php
    [$rooms, $beds, $bathrooms] = $property['details'];
    $amenityIcons = ['parking' => 'car', 'wifi' => 'wifi', 'heater' => 'wind', 'cleaning' => 'sun'];
    $plural = fn (int $value, string $word): string => $value.' '.$word.($value === 1 ? '' : 's');
@endphp

<article class="property-list-card" data-layout-item data-search="{{ Illuminate\Support\Str::lower($property['name'].' '.$property['location'].' '.implode(' ', $property['amenities'])) }}" style="--item-order: {{ $index }}">
    <div class="property-card__inner">
        <img class="property-card__picture" src="{{ $property['picture'] }}" alt="{{ $property['name'] }}">
        <div class="property-card__content">
            <h2><span>{{ $property['name'] }}</span><span class="property-card__rating" aria-label="{{ $property['rating'] }} out of 5 stars">
                @for ($star = 1; $star <= 5; $star++)<x-vuero.icon name="star" size="13" class="{{ $star <= $property['rating'] ? 'is-active' : '' }}" />@endfor
            </span></h2>
            <p class="property-card__location"><x-vuero.icon name="location" size="12" /><span>{{ $property['location'] }}</span></p>
            <p class="property-card__details"><span>{{ $plural($rooms, 'room') }}</span><i></i><span>{{ $plural($beds, 'bed') }}</span><i></i><span>{{ $plural($bathrooms, 'bathroom') }}</span></p>
            <div class="property-card__amenities">
                @foreach ($property['amenities'] as $amenity)
                    <span><x-vuero.icon :name="$amenityIcons[$amenity]" size="18" /><small>{{ ucfirst($amenity) }}</small></span>
                @endforeach
                @if ($property['more'] > 0)<span><x-vuero.icon name="more" size="18" /><small>{{ $property['more'] }} more</small></span>@endif
            </div>
        </div>
        <div class="property-card__actions">
            <button type="button" class="vuero-list-button is-light" data-property-action="info" data-item-name="{{ $property['name'] }}">More Info</button>
            <button type="button" class="vuero-list-button is-primary" data-property-action="book" data-item-name="{{ $property['name'] }}">Book Now</button>
        </div>
    </div>
</article>
