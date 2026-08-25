@props([
    'dashboard',
    'script',
    'class' => '',
])

@php
    $chrome = match ($dashboard['slug']) {
        'inbox', 'messaging-v2' => 'immersive',
        'maps-1', 'maps-2', 'messaging-v1' => 'sidebar-app',
        default => 'default',
    };
@endphp

<x-vuero.layouts.app :title="$dashboard['title']" :script="$script" :chrome="$chrome">
    <section
        class="vuero-dashboard vuero-dashboard--{{ $dashboard['group'] }} vuero-dashboard--{{ $dashboard['slug'] }} {{ $class }}"
        data-dashboard-page="{{ $dashboard['slug'] }}"
        aria-label="{{ $dashboard['title'] }}"
    >
        {{ $slot }}
    </section>
    <x-vuero.dashboard.feedback.action-layer />
</x-vuero.layouts.app>
