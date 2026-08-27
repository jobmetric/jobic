@php
    $charts = [
        'line' => 'Line Chart',
        'multiple-lines' => 'Multiple Lines',
        'stepline' => 'Stepline Chart',
        'area' => 'Area Chart',
        'multiple-area' => 'Multiple Area',
        'multiple-areas' => 'Multiple Areas',
        'bar' => 'Bar Chart',
        'multiple-bars' => 'Multiple Bars',
        'stacked-bars' => 'Stacked Bars',
        'range-column' => 'Range Column',
        'horizontal-bar' => 'Horizontal Bar',
        'horizontal-bar-multiple' => 'Horizontal Bar Multiple',
        'timeline' => 'Timeline',
        'bubble' => 'Bubble Chart',
        'scatter' => 'Scatter Chart',
        'pie' => 'Pie Chart',
        'donut' => 'Donut Chart',
        'radial-bar' => 'Radial Bar',
        'radial-bar-multiple' => 'Radial Bar Multiple',
        'radial-bar-gauge' => 'Radial Bar Gauge',
        'radial-bar-stroked' => 'Radial Bar Gauge Stroked',
        'radar' => 'Radar Chart',
    ];
@endphp

<x-vuero.charts.page :dashboard="$dashboard" :script="$dashboardScript" engine="apex" :charts="$charts" />
