@php
    $charts = [
        'line' => 'Line Chart',
        'regions' => 'Line Chart with Regions',
        'area' => 'Area Chart',
        'area-range' => 'Area Range Chart',
        'bar' => 'Bar Chart',
        'stacked-bar' => 'Stacked Bar Chart',
        'step' => 'Step Chart',
        'spline' => 'Spline Chart',
        'bubble' => 'Bubble Chart',
        'scatter' => 'Scatter Plot',
        'pie' => 'Pie Chart',
        'donut' => 'Donut Chart',
        'gauge' => 'Gauge Chart',
        'radar' => 'Radar Chart',
    ];
@endphp

<x-vuero.charts.page :dashboard="$dashboard" :script="$dashboardScript" engine="billboard" :charts="$charts" />
