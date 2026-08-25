@props([
    'symbol', 'name', 'brand', 'change', 'tone', 'path', 'tail',
    'pointX', 'pointY', 'labelX', 'labelY',
])

@php($gradientId = 'stock-gradient-' . strtolower($symbol))

<article class="stock-graph-card stock-tone--{{ $tone }}" data-stock-symbol="{{ $symbol }}">
    <x-vuero.dashboard.stocks.menu context="graph" :label="$symbol . ' stock actions'" />

    <svg class="stock-chart" viewBox="0 0 250 80" role="img" aria-label="{{ $name }} performance: {{ $change }}">
        <defs>
            <linearGradient id="{{ $gradientId }}" x1="0%" y1="50%" x2="100%" y2="50%">
                <stop offset="0%" class="stock-chart__gradient-start" />
                <stop offset="100%" class="stock-chart__gradient-end" />
            </linearGradient>
        </defs>
        <path class="stock-chart__tail" d="{{ $tail }}" pathLength="1" />
        <path class="stock-chart__line" d="{{ $path }}" pathLength="1" stroke="url(#{{ $gradientId }})" />
        <circle class="stock-chart__point" cx="{{ $pointX }}" cy="{{ $pointY }}" r="2.7" />
        <text class="stock-chart__change" x="{{ $labelX }}" y="{{ $labelY }}">{{ $change }}</text>
    </svg>

    <x-vuero.dashboard.stocks.identity :symbol="$symbol" :name="$name" :brand="$brand" />
</article>
