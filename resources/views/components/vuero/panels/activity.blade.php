<section class="side-panel-layer" id="activity-panel" data-side-panel="activity" aria-hidden="true">
    <button class="side-panel-layer__overlay" type="button" tabindex="-1" data-panel-close aria-label="Close activity panel"></button>

    <aside class="side-panel" role="dialog" aria-modal="true" aria-labelledby="activity-panel-title" tabindex="-1">
        <header class="side-panel__header">
            <h2 id="activity-panel-title">Activity</h2>
            <button class="side-panel__close" type="button" data-panel-close aria-label="Close activity panel">
                <x-vuero.icon name="chevron-right" size="20" />
            </button>
        </header>

        <div class="panel-tabs" data-tabs>
            <div class="panel-tabs__nav" role="tablist" aria-label="Activity views">
                <button class="is-active" type="button" role="tab" aria-selected="true" aria-controls="activity-team" data-tab-target="team">Team</button>
                <button type="button" role="tab" aria-selected="false" aria-controls="activity-projects" data-tab-target="projects">Projects</button>
                <button type="button" role="tab" aria-selected="false" aria-controls="activity-schedule" data-tab-target="schedule">Schedule</button>
            </div>

            <div class="side-panel__body">
                <div class="panel-tabs__content is-active" id="activity-team" role="tabpanel" data-tab-content="team">
                    @foreach ([
                        ['initials' => 'JS', 'name' => 'Joshua S.', 'location' => 'Las Vegas, NV', 'tone' => 'blue'],
                        ['initials' => 'MW', 'name' => 'Melany W.', 'location' => 'San Jose, CA', 'tone' => 'orange'],
                        ['initials' => 'EC', 'name' => 'Esteban C.', 'location' => 'Miami, FL', 'tone' => 'purple'],
                        ['initials' => 'TS', 'name' => 'Tara S.', 'location' => 'New York, NY', 'tone' => 'green'],
                    ] as $person)
                        <article class="activity-person">
                            <span class="activity-avatar activity-avatar--{{ $person['tone'] }}">{{ $person['initials'] }}</span>
                            <div><strong>{{ $person['name'] }}</strong><small><x-vuero.icon name="location" size="13" /> {{ $person['location'] }}</small></div>
                            <button type="button" aria-label="Open {{ $person['name'] }} profile"><x-vuero.icon name="chevron-right" size="16" /></button>
                        </article>
                    @endforeach
                </div>

                <div class="panel-tabs__content" id="activity-projects" role="tabpanel" data-tab-content="projects" hidden>
                    @foreach ([['Website Redesign', 67], ['Mobile Application', 40], ['Brand Guidelines', 25]] as [$project, $progress])
                        <article class="activity-project">
                            <header><div><strong>{{ $project }}</strong><small>{{ $progress }}% completed</small></div><span>{{ $progress }}%</span></header>
                            <div class="vuero-progress"><span style="width: {{ $progress }}%"></span></div>
                        </article>
                    @endforeach
                </div>

                <div class="panel-tabs__content" id="activity-schedule" role="tabpanel" data-tab-content="schedule" hidden>
                    <div class="schedule-day"><span>Today</span><strong>October 18</strong></div>
                    <article class="schedule-item"><time>09:30</time><i class="is-primary"></i><div><strong>Product meeting</strong><small>Design team · 45 min</small></div></article>
                    <article class="schedule-item"><time>12:00</time><i class="is-orange"></i><div><strong>Release review</strong><small>Development team · 30 min</small></div></article>
                    <article class="schedule-item"><time>15:30</time><i class="is-purple"></i><div><strong>Weekly report</strong><small>Analytics dashboard</small></div></article>
                </div>
            </div>
        </div>
    </aside>
</section>

<div class="ui-toast" role="status" aria-live="polite" aria-atomic="true" data-ui-toast hidden>
    <x-vuero.icon name="check" size="17" />
    <span data-ui-toast-message></span>
</div>
