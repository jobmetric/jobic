@props(['dashboard', 'script'])

<x-vuero.dashboard.page :dashboard="$dashboard" :script="$script" class="vuero-widgets-page vuero-widgets-page--{{ $dashboard['slug'] }}">
    {{ $slot }}
</x-vuero.dashboard.page>
