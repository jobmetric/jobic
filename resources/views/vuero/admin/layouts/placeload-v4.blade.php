<x-vuero.layouts.app title="Placeload V4" :script="$layoutScript">
    <section class="placeload-page placeload-tile-v1" data-placeload-v4 aria-label="Placeload V4">
        <header class="placeload-toolbar user-grid-toolbar">
            <label class="placeload-search">
                <span class="sr-only">Search</span>
                <x-vuero.icon name="search" size="17" />
                <input type="search" placeholder="Search..." autocomplete="off" data-placeload-search>
            </label>
            <div class="placeload-actions">
                <select class="placeload-filter h-hidden-mobile" aria-label="Filter tiles">
                    <option selected>All</option>
                    <option>UI/UX Design</option>
                    <option>Web Development</option>
                    <option>Software Eng.</option>
                    <option>Business</option>
                </select>
                <button type="button" class="placeload-add" data-placeload-add>
                    <x-vuero.icon name="plus" size="15" />
                    <span>Add User</span>
                </button>
            </div>
        </header>

        <div class="tile-grid tile-grid-v1">
            <div class="columns is-multiline">
                @foreach (range(1, 30) as $ignored)
                    <x-vuero.layouts.placeload-tile-item />
                @endforeach
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
