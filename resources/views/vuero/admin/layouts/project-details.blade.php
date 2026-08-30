<x-vuero.layouts.app title="Project Details" :script="$layoutScript">
    <section class="project-details" data-project-details aria-label="Project Details">
        <div class="tabs-wrapper is-triple-slider">
            <div class="tabs-inner">
                <div class="tabs">
                    <ul role="tablist" aria-label="Project sections">
                        <li class="is-active" data-project-tab="project"><button id="project-tab-project" type="button" role="tab" aria-selected="true" aria-controls="project-panel-project" tabindex="0"><span>Project</span></button></li>
                        <li data-project-tab="team"><button id="project-tab-team" type="button" role="tab" aria-selected="false" aria-controls="project-panel-team" tabindex="-1"><span>Team</span></button></li>
                        <li data-project-tab="tasks"><button id="project-tab-tasks" type="button" role="tab" aria-selected="false" aria-controls="project-panel-tasks" tabindex="-1"><span>Tasks</span></button></li>
                        <li class="tab-naver" aria-hidden="true"></li>
                    </ul>
                </div>
            </div>

            <div id="project-panel-project" class="tab-content is-active" role="tabpanel" aria-labelledby="project-tab-project" data-project-panel="project">
                <div class="columns project-details-inner">
                    <div class="column is-8">
                        <div class="project-details-card">
                            <div class="card-head">
                                <div class="title-wrap">
                                    <h3>Brand and Website Redesign</h3>
                                    <p>UI/UX Design</p>
                                </div>
                                <button type="button" class="button is-circle is-light" aria-label="Edit project" data-project-edit>
                                    <x-vuero.icon name="edit" size="16" />
                                </button>
                            </div>

                            <div class="project-overview">
                                <p>
                                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quid iudicant
                                    sensus? Primum quid tu dicis breve? Etiam beatissimum? Ne discipulum
                                    abducam, times.
                                </p>
                                <x-vuero.layouts.flex-avatar-stack :avatars="$participants" :limit="5" />
                            </div>

                            <div class="project-features">
                                @foreach ($features as $feature)
                                    <div class="project-feature">
                                        <x-vuero.icon :name="$feature['icon']" size="26" />
                                        <h4>{{ $feature['title'] }}</h4>
                                        <p>{{ $feature['text'] }}</p>
                                    </div>
                                @endforeach
                            </div>

                            <div class="project-files">
                                <h4>Recent Files</h4>
                                <div class="columns is-multiline">
                                    @foreach ($files as $file)
                                        <div class="column is-6">
                                            <div class="file-box">
                                                <img src="{{ $file['icon'] }}" alt="">
                                                <div class="meta">
                                                    <span>{{ $file['name'] }}</span>
                                                    <span>{{ $file['meta'] }}</span>
                                                </div>
                                                <x-vuero.layouts.flex-table-menu type="file" label="File actions" />
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="column is-4">
                        @foreach ($sideCards as $card)
                            <div class="side-card">
                                <h4>{{ $card['title'] }}</h4>
                                @foreach ($card['items'] as $item)
                                    <div class="media-flex-center">
                                        <img src="{{ $item['icon'] }}" alt="">
                                        <div class="meta">
                                            <span>{{ $item['title'] }}</span>
                                            <span>{{ $item['subtitle'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="project-panel-team" class="tab-content" role="tabpanel" aria-labelledby="project-tab-team" data-project-panel="team" hidden>
                <div class="project-details-inner">
                    <div class="project-team-card">
                        <div class="columns is-multiline">
                            @foreach ($team as $index => $member)
                                <div @class(['column is-6', 'has-border-bottom' => $index < count($team) - 2])>
                                    <div class="media-flex-center">
                                        <img src="{{ $member['avatar'] }}" alt="">
                                        <div class="meta">
                                            <span>{{ $member['name'] }}</span>
                                            <span>{{ $member['role'] }}</span>
                                        </div>
                                        <x-vuero.layouts.flex-table-menu type="team" label="Member actions" />
                                    </div>
                                    <p>{{ $member['bio'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div id="project-panel-tasks" class="tab-content" role="tabpanel" aria-labelledby="project-tab-tasks" data-project-panel="tasks" hidden>
                <div class="project-details-inner">
                    <div class="task-grid">
                        <div class="grid-header">
                            <h3>Tasks</h3>
                            <div class="filter">
                                <span>Filter by</span>
                                <x-vuero.forms.select
                                    name="task-filter"
                                    :options="['New', 'In Progress', 'Closed', 'Archived']"
                                    placeholder="In Progress"
                                    data-project-task-filter
                                />
                            </div>
                        </div>

                        <div class="columns is-multiline">
                            @foreach ($tasks as $task)
                                <div class="column is-4">
                                    <article class="task-card" data-project-task>
                                        <div class="title-wrap">
                                            <h3>{{ $task['title'] }}</h3>
                                            <span>{{ $task['progress'] }}%</span>
                                        </div>
                                        <div class="content-wrap">
                                            <div class="left">
                                                <x-vuero.layouts.flex-avatar-stack :avatars="$task['team']" :limit="3" />
                                                <div class="attachments">
                                                    <x-vuero.icon name="paperclip" size="15" />
                                                    <span>{{ $task['attachments'] }}</span>
                                                </div>
                                            </div>
                                            <x-vuero.layouts.circle-chart :value="$task['progress']" :tone="$task['tone']" />
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-vuero.layouts.app>
