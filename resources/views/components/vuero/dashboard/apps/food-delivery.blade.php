@php
    $categories = [
        ['slug' => 'all', 'label' => 'All', 'icon' => 'icon-1'],
        ['slug' => 'pizza', 'label' => 'Pizza', 'icon' => 'icon-2'],
        ['slug' => 'asian', 'label' => 'Asian', 'icon' => 'icon-3'],
        ['slug' => 'burgers', 'label' => 'Burgers', 'icon' => 'icon-4'],
        ['slug' => 'barbecue', 'label' => 'Barbecue', 'icon' => 'icon-5'],
        ['slug' => 'desserts', 'label' => 'Desserts', 'icon' => 'icon-6'],
        ['slug' => 'thai', 'label' => 'Thai', 'icon' => 'icon-7'],
        ['slug' => 'bagels', 'label' => 'Bagels', 'icon' => 'icon-8'],
    ];

    $restaurants = [
        ['name' => 'Bagel Story', 'category' => 'bagels', 'tags' => 'Deli · Bagels', 'rating' => '4.7', 'time' => '25–30', 'price' => '14.99', 'dish' => 'Salmon Bagel', 'sprite' => 1, 'icon' => 'icon-8'],
        ['name' => 'Dessert Rose', 'category' => 'desserts', 'tags' => 'Cafés · Desserts', 'rating' => '4.5', 'time' => '30–35', 'price' => '11.50', 'dish' => 'Berry Cheesecake', 'sprite' => 2, 'icon' => 'icon-6'],
        ['name' => 'Barbecue Nation', 'category' => 'barbecue', 'tags' => 'Barbecue · Chicken', 'rating' => '4.6', 'time' => '40–60', 'price' => '18.75', 'dish' => 'Smoked Chicken', 'sprite' => 3, 'icon' => 'icon-5'],
        ['name' => 'Napoli Centrale', 'category' => 'pizza', 'tags' => 'Italian · Pizza', 'rating' => '4.9', 'time' => '20–25', 'price' => '16.25', 'dish' => 'Margherita Pizza', 'sprite' => 4, 'icon' => 'icon-2'],
        ['name' => 'Thai Garden', 'category' => 'asian thai', 'tags' => 'Asian · Thai', 'rating' => '4.8', 'time' => '25–35', 'price' => '15.40', 'dish' => 'Pad Thai Bowl', 'sprite' => 5, 'icon' => 'icon-7'],
        ['name' => 'Burger Lab', 'category' => 'burgers', 'tags' => 'Burgers · American', 'rating' => '4.7', 'time' => '20–30', 'price' => '13.95', 'dish' => 'Signature Burger', 'sprite' => 6, 'icon' => 'icon-4'],
    ];
@endphp

<div class="food-delivery-app" data-food-delivery>
    <main class="food-delivery-main">
        <x-vuero.dashboard.apps.food-delivery.promotion />

        <header class="food-delivery-browser">
            <div class="food-delivery-browser__title">
                <h2>Restaurants</h2>
                <span aria-hidden="true"><x-vuero.icon name="food" size="19" /></span>
            </div>
            <div class="food-delivery-browser__actions">
                <label class="food-delivery-search">
                    <span class="sr-only">Search restaurants</span>
                    <x-vuero.icon name="search" size="17" />
                    <input type="search" placeholder="Search restaurants" autocomplete="off" data-food-search>
                    <button type="button" data-food-search-clear aria-label="Clear restaurant search" hidden><x-vuero.icon name="x" size="15" /></button>
                </label>
                <button type="button" class="food-delivery-time" data-food-time aria-haspopup="dialog">
                    <x-vuero.icon name="clock" size="16" />
                    <span>Delivery: <strong data-food-time-label>Now</strong></span>
                    <x-vuero.icon name="chevron-right" size="14" />
                </button>
            </div>
        </header>

        <x-vuero.dashboard.apps.food-delivery.categories :categories="$categories" />
        <x-vuero.dashboard.apps.food-delivery.restaurants :restaurants="$restaurants" />
    </main>

    <x-vuero.dashboard.apps.food-delivery.order />
</div>
