<x-vuero.layouts.app title="Placeload V3" :script="$layoutScript">
    <section class="placeload-page placeload-grid-v1" data-placeload-v3 aria-label="Placeload V3">
        <header class="placeload-toolbar user-grid-toolbar">
            <label class="placeload-search">
                <span class="sr-only">Search</span>
                <x-vuero.icon name="search" size="17" />
                <input type="search" placeholder="Search..." autocomplete="off" data-placeload-search>
            </label>
            <div class="placeload-actions">
                <select class="placeload-filter h-hidden-mobile" aria-label="Filter users">
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

        <div class="user-grid user-grid-v1">
            <div class="columns is-multiline">
                @foreach (range(1, 20) as $ignored)
                    <x-vuero.layouts.placeload-grid-item />
                @endforeach
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
