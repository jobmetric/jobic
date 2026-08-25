@props(['restaurants'])

<section class="food-delivery-restaurants" aria-label="Available restaurants" data-food-restaurants>
    @foreach ($restaurants as $restaurant)
        <article
            class="food-delivery-restaurant"
            data-food-restaurant
            data-category="{{ $restaurant['category'] }}"
            data-name="{{ $restaurant['name'] }}"
            data-dish="{{ $restaurant['dish'] }}"
            data-price="{{ $restaurant['price'] }}"
            data-sprite="{{ $restaurant['sprite'] }}"
            role="button"
            tabindex="0"
            aria-label="Add {{ $restaurant['dish'] }} from {{ $restaurant['name'] }} to order"
        >
            <div class="food-delivery-restaurant__image food-sprite-{{ $restaurant['sprite'] }}" aria-hidden="true">
                <span><strong>{{ $restaurant['time'] }}</strong> min</span>
            </div>
            <footer>
                <span class="food-delivery-restaurant__icon"><img src="{{ asset('vuero/images/icons/food/'.$restaurant['icon'].'.svg') }}" alt=""></span>
                <div>
                    <h3>{{ $restaurant['name'] }}</h3>
                    <p>{{ $restaurant['tags'] }} <span>·</span> <b><x-vuero.icon name="star" size="12" /> {{ $restaurant['rating'] }}</b></p>
                </div>
            </footer>
        </article>
    @endforeach

    <div class="food-delivery-no-results" data-food-no-results hidden>
        <span><x-vuero.icon name="search" size="25" /></span>
        <h3>No restaurants found</h3>
        <p>Try another category or clear your search.</p>
        <button type="button" data-food-reset>Show all restaurants</button>
    </div>
</section>
