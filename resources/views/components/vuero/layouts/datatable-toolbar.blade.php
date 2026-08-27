@props(['ctaLabel' => 'Add User'])

<header class="list-layout-toolbar datatable-toolbar">
    <label class="list-layout-search">
        <span class="sr-only">Search</span>
        <x-vuero.icon name="search" size="17" />
        <input type="search" placeholder="Search..." autocomplete="off" data-layout-filter>
        <button type="button" aria-label="Clear search" data-layout-filter-clear hidden><x-vuero.icon name="x" size="15" /></button>
    </label>

    <button type="button" class="vuero-list-button is-primary datatable-toolbar__cta" data-datatable-cta>
        <x-vuero.icon name="plus" size="15" />
        <span>{{ $ctaLabel }}</span>
    </button>
</header>
