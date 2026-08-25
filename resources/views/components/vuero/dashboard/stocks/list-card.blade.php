@props(['title', 'stocks'])

<article class="stock-list-card">
    <header class="stock-list-card__header">
        <h2>{{ $title }}</h2>
        <x-vuero.dashboard.stocks.menu context="list" :label="$title . ' actions'" />
    </header>

    <div class="stock-list">
        @foreach ($stocks as [$symbol, $name, $value, $brand])
            <button type="button" class="stock-list__row" data-stock-symbol="{{ $symbol }}">
                <x-vuero.dashboard.stocks.identity :symbol="$symbol" :name="$name" :brand="$brand" />
                <strong class="stock-list__value">{{ $value }}</strong>
            </button>
        @endforeach
    </div>
</article>
