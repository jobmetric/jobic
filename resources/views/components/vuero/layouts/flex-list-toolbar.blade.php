<header class="list-layout-toolbar flex-list-toolbar">
    <label class="list-layout-search">
        <span class="sr-only">Search</span>
        <x-vuero.icon name="search" size="17" />
        <input type="search" placeholder="Search..." autocomplete="off" data-layout-filter>
        <button type="button" aria-label="Clear search" data-layout-filter-clear hidden><x-vuero.icon name="x" size="15" /></button>
    </label>

    <button type="button" class="vuero-list-button is-primary flex-list-add" data-flex-add-user>
        <x-vuero.icon name="plus" size="15" />
        <span>Add User</span>
    </button>
</header>
