<x-vuero.layouts.app title="Projects projects 3" :script="$layoutScript">
    <section class="projects-page all-projects" data-projects-v3 aria-label="Projects 3">
        <div class="illustration-header">
            <img class="light-image" src="/vuero/images/projects/header.svg" alt="">
            <img class="dark-image" src="/vuero/images/projects/header-dark.svg" alt="">
            <div class="header-stats">
                <div class="stats-inner">
                    @foreach ($stats as $stat)
                        <div class="stat-item">
                            <span class="{{ $stat['class'] }}">{{ $stat['value'] }}</span>
                            <p>{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="recent-projects">
            <h3 class="recent-heading">Recent Projects</h3>
            <div class="columns">
                @foreach ($recent as $item)
                    <div class="column is-4">
                        <a href="#" class="project-box" data-project-recent>
                            <x-vuero.layouts.circle-chart :value="$item['progress']" :tone="$item['tone']" />
                            <h3>{{ $item['title'] }}</h3>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="project-minimal-grid">
            <div class="grid-header">
                <h3>Projects</h3>
                <div class="filter">
                    <span>Filter by</span>
                    <x-vuero.forms.select
                        name="project-filter"
                        :options="['Shipping Soon', 'In Progress', 'Blocked', 'Archived']"
                        placeholder="Shipping Soon"
                        data-projects-filter
                    />
                </div>
            </div>

            <div class="columns is-multiline grid-body" data-projects-grid>
                @foreach ($projects as $project)
                    <div class="column is-4" data-project-status="{{ $project['filter'] }}">
                        <x-vuero.layouts.project-minimal-item :project="$project" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
