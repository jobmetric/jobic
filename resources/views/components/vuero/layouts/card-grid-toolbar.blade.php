@props(['filterOptions', 'filterSelected', 'ctaLabel'])

<header class="card-grid-toolbar">
    <label class="card-grid-search">
        <span class="sr-only">Search</span>
        <x-vuero.icon name="search" size="17" />
        <input type="search" class="input custom-text-filter" placeholder="Search..." autocomplete="off" data-grid-filter>
        <button type="button" aria-label="Clear search" data-grid-filter-clear hidden><x-vuero.icon name="x" size="15" /></button>
    </label>

    <div class="buttons">
        <div class="field h-hidden-mobile card-grid-multiselect">
            <x-vuero.forms.select :options="$filterOptions" :selected="$filterSelected" class="card-grid-filter" />
        </div>
        <button type="button" class="button v-button is-primary is-raised" data-grid-cta>
            <span class="icon"><x-vuero.icon name="plus" size="14" /></span>
            <span>{{ $ctaLabel }}</span>
        </button>
    </div>
</header>
