<x-vuero.layouts.app
    :title="$starter['title']"
    :script="$starterScript"
    :layout="$starter['layout']"
    :starter="$starter"
    :open-navigation="$starter['open'] ?? false"
>
    <section
        class="vuero-starter-page vuero-starter-page--{{ $starter['family'] }}"
        data-starter-page="{{ $starter['slug'] }}"
        data-starter-family="{{ $starter['family'] }}"
        data-starter-layout="{{ $starter['layout'] }}"
        data-starter-reference="{{ $starter['reference'] }}"
        aria-label="{{ $starter['title'] }} {{ $starter['familyLabel'] }} starter"
    >
        <h2 class="sr-only">{{ $starter['title'] }} content area</h2>
    </section>
</x-vuero.layouts.app>
