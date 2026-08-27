@props([
    'dashboard',
    'script',
    'engine',
    'charts',
])

<x-vuero.dashboard.page :dashboard="$dashboard" :script="$script" class="vuero-charts-page vuero-charts-page--{{ $engine }}">
    <div class="vuero-charts-grid" data-charts-grid="{{ $engine }}">
        @foreach ($charts as $chart => $label)
            <x-vuero.charts.chart-card :engine="$engine" :chart="$chart" :label="$label" />
        @endforeach
    </div>
</x-vuero.dashboard.page>
