<x-vuero.layouts.app title="Placeload V2" :script="$layoutScript">
    <section class="placeload-page vuero-flex-list vuero-flex-list-v1" data-placeload-v2 aria-label="Placeload V2">
        <header class="placeload-toolbar flex-list-toolbar">
            <label class="placeload-search">
                <span class="sr-only">Search</span>
                <x-vuero.icon name="search" size="17" />
                <input type="search" placeholder="Search..." autocomplete="off" data-placeload-search>
            </label>
            <button type="button" class="placeload-add" data-placeload-add>
                <x-vuero.icon name="plus" size="15" />
                <span>Add User</span>
            </button>
        </header>

        <div class="flex-list-wrapper">
            <x-vuero.layouts.flex-table
                :compact="true"
                :rounded="true"
                :columns="[
                    ['label' => 'User', 'grow' => true],
                    ['label' => 'Location'],
                    ['label' => 'Industry'],
                    ['label' => 'Status'],
                    ['label' => 'Relations'],
                    ['label' => 'Actions', 'align' => 'end'],
                ]"
            >
                @foreach (range(1, 10) as $ignored)
                    <x-vuero.layouts.placeload-flex-row />
                @endforeach
            </x-vuero.layouts.flex-table>
        </div>
    </section>
</x-vuero.layouts.app>
