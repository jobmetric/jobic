@props(['name', 'size' => 36])

@php
    $iconMap = [
        'lnil-consulting' => 'briefcase',
        'lnil-tie' => 'user',
        'lnil-handshake' => 'user-check',
        'lnil-licencse' => 'award',
        'lnil-pie-chart-alt' => 'pie-chart',
        'lnil-customer' => 'users',
    ];

    $iconName = $iconMap[$name] ?? 'briefcase';
@endphp

<i {{ $attributes->class(['lnil', $name]) }} aria-hidden="true">
    <x-vuero.icon :name="$iconName" :size="$size" class="lnil-icon" />
</i>
