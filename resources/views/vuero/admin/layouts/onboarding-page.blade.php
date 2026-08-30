<x-vuero.layouts.app title="Onboarding {{ strtoupper($variant) }}" :script="$layoutScript">
    <x-vuero.layouts.onboarding-page :variant="$variant" :cards="$cards" />
</x-vuero.layouts.app>
