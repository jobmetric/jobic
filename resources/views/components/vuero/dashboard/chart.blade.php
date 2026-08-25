@props([
    'type' => 'area',
    'tone' => 'primary',
    'height' => 190,
])

<div {{ $attributes->class(['dashboard-chart', "dashboard-chart--{$type}", "dashboard-chart--{$tone}"]) }} style="--chart-height: {{ $height }}px" role="img" aria-label="Dashboard chart" data-vuero-chart data-chart-type="{{ $type }}" data-chart-tone="{{ $tone }}" data-chart-height="{{ $height }}">
    @if ($type === 'bars')
        <x-vuero.dashboard.charts.bars />
    @else
        <x-vuero.dashboard.charts.area :tone="$tone" :uid="spl_object_id($attributes)" />
    @endif
    <div class="dashboard-chart__apex" aria-hidden="true" data-chart-apex></div>
</div>
