<x-vuero.layouts.app title="Placeload V1" :script="$layoutScript">
    <section class="placeload-page placeload-list-v1" data-placeload-v1 aria-label="Placeload V1">
        <header class="placeload-toolbar">
            <label class="placeload-search">
                <span class="sr-only">Search</span>
                <x-vuero.icon name="search" size="17" />
                <input type="search" placeholder="Search..." autocomplete="off" data-placeload-search>
            </label>
            <p class="list-info"><span>Loading records</span></p>
            <button type="button" class="placeload-approve" disabled aria-disabled="true">
                <x-vuero.icon name="check" size="15" />
                <span>Approve</span>
            </button>
        </header>

        <div class="list-view list-view-v1">
            <div class="list-view-inner">
                @foreach (range(1, 10) as $ignored)
                    <x-vuero.layouts.placeload-list-item />
                @endforeach
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
