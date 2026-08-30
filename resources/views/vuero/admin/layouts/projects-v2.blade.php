<x-vuero.layouts.app title="Projects projects 2" :script="$layoutScript">
    <section class="projects-page all-projects" data-projects-v2 aria-label="Projects 2">
        <x-vuero.layouts.projects-toolbar :members="$members" />

        <div class="all-projects-header">
            @foreach ($stats as $stat)
                <div class="header-item">
                    <div class="item-inner">
                        <x-vuero.icon :name="$stat['icon']" size="35" />
                        <span>{{ $stat['value'] }}</span>
                        <p>{{ $stat['label'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="columns is-multiline projects-card-grid">
            @foreach ($projects as $project)
                <div class="column is-6">
                    <x-vuero.layouts.project-card-item :project="$project" />
                </div>
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
