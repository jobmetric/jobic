@props([
    'label',
    'value',
    'caption' => null,
    'tone' => 'primary',
    'icon' => 'activity',
])

<div {{ $attributes->class(['dashboard-stat', "dashboard-stat--{$tone}"]) }}>
    <span class="dashboard-stat__icon"><x-vuero.icon :name="$icon" size="19" /></span>
    <div>
        <strong>{{ $value }}</strong>
        <span>{{ $label }}</span>
        @if ($caption)<small>{{ $caption }}</small>@endif
    </div>
</div>
