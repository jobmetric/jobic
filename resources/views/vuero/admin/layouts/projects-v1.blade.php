<x-vuero.layouts.app title="Projects projects" :script="$layoutScript">
    <section class="projects-page all-projects" data-projects-v1 aria-label="Projects 1">
        <x-vuero.layouts.projects-toolbar :members="$members" />

        <h3 class="section-heading">Recently viewed</h3>

        <div class="columns is-multiline project-grid">
            @foreach ($recent as $item)
                <div class="column is-one-fifth">
                    <a href="#" class="project-grid-item" data-project-recent>
                        <img class="project-avatar" src="{{ $item['logo'] }}" alt="">
                        <h3>{{ $item['name'] }}</h3>
                        <p>{{ $item['type'] }}</p>
                        <div class="progress-tiny">
                            <progress max="100" value="{{ $item['progress'] }}"></progress>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        <div class="columns">
            <div class="column is-12">
                <x-vuero.layouts.flex-table
                    :rounded="true"
                    :columns="[
                        ['label' => 'Name', 'grow' => true],
                        ['label' => 'Position'],
                        ['label' => 'Revenue'],
                        ['label' => 'Status'],
                        ['label' => 'Contacts'],
                        ['label' => 'Actions', 'align' => 'end'],
                    ]"
                    data-projects-table
                >
                    @foreach ($users as $user)
                        <x-vuero.layouts.flex-advanced-row :row="$user" />
                    @endforeach
                </x-vuero.layouts.flex-table>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
