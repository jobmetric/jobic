@props(['tabs', 'active', 'reversed' => false])

<header class="list-layout-toolbar {{ $reversed ? 'is-reversed' : '' }}">
    <label class="list-layout-search">
        <span class="sr-only">Search</span>
        <x-vuero.icon name="search" size="17" />
        <input type="search" placeholder="Search..." autocomplete="off" data-layout-filter>
        <button type="button" aria-label="Clear search" data-layout-filter-clear hidden><x-vuero.icon name="x" size="15" /></button>
    </label>

    <div class="list-layout-tabs" role="tablist" aria-label="List filters">
        @foreach ($tabs as $id => $label)
            <button type="button" role="tab" aria-selected="{{ $id === $active ? 'true' : 'false' }}" class="{{ $id === $active ? 'is-active' : '' }}" data-layout-tab="{{ $id }}">{{ $label }}</button>
        @endforeach
        <span class="list-layout-tabs__naver" aria-hidden="true"></span>
    </div>
</header>
