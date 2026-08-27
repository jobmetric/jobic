@props([
    'engine',
    'chart',
    'label',
])

<article class="vuero-chart-card" data-chart-card="{{ $chart }}">
    <div
        class="vuero-chart-card__canvas"
        data-{{ $engine }}-chart="{{ $chart }}"
        role="img"
        aria-label="{{ $label }}"
    ></div>
    <div class="vuero-chart-card__loader" data-chart-loader aria-hidden="true"><span></span></div>
</article>
