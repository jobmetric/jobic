@props(['categories'])

<div class="food-delivery-category-shell">
    <button type="button" class="food-delivery-category-arrow is-previous" data-food-category-previous aria-label="Previous food categories">
        <x-vuero.icon name="chevron-left" size="17" />
    </button>
    <div class="food-delivery-categories" role="tablist" aria-label="Restaurant categories" data-food-categories tabindex="-1">
        @foreach ($categories as $category)
            <button
                type="button"
                class="food-delivery-category {{ $loop->first ? 'is-active' : '' }}"
                role="tab"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                data-food-category="{{ $category['slug'] }}"
            >
                <span><img src="{{ asset('vuero/images/icons/food/'.$category['icon'].'.svg') }}" alt=""></span>
                <strong>{{ $category['label'] }}</strong>
            </button>
        @endforeach
    </div>
    <button type="button" class="food-delivery-category-arrow is-next" data-food-category-next aria-label="Next food categories">
        <x-vuero.icon name="chevron-right" size="17" />
    </button>
</div>
