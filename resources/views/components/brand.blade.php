@props(['compact' => false])
<span {{ $attributes->class(['brand', 'brand--compact' => $compact]) }}>
    <img class="brand__mark cms-brand-mark" src="{{ asset('images/brand/jobmetric.png') }}" alt="JobMetric" width="48" height="48" />
    @unless($compact)<span class="brand__name">{{ config('app.name', 'Jobic') }}</span>@endunless
</span>
