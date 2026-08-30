<x-vuero.layouts.app title="Kanban Board" :script="$layoutScript">
    <section class="kanban-content" data-kanban-board aria-label="Kanban Board">
        <div class="kanban-toolbar">
            <div class="field">
                <div class="control has-icon">
                    <input class="input" type="search" placeholder="Search..." data-kanban-search aria-label="Search tasks">
                    <span class="form-icon"><x-vuero.icon name="search" size="17" /></span>
                </div>
            </div>

            <x-vuero.layouts.flex-avatar-stack :avatars="$participants" :limit="5" />

            <button type="button" class="button v-button is-primary is-raised is-bold" data-kanban-new>
                New Task
            </button>
        </div>

        <div class="columns is-kanban-wrapper">
            @foreach ($columns as $column)
                <div class="column is-one-fifth" data-kanban-column-wrap="{{ $column['state'] }}">
                    <div
                        class="kanban-column {{ $column['draggable'] ? 'can-drag' : '' }} {{ count($column['tasks']) === 0 ? 'is-empty' : '' }}"
                        data-kanban-column="{{ $column['state'] }}"
                    >
                        <div class="collapsed-content">
                            <button type="button" class="expand-button" data-kanban-expand="{{ $column['state'] }}" aria-label="Expand {{ $column['label'] }} column">
                                <x-vuero.icon name="plus" size="18" />
                            </button>
                            <div><span class="task-count" data-kanban-count="{{ $column['state'] }}">{{ count($column['tasks']) }}</span></div>
                            <div class="collapsed-text">{{ $column['label'] }}</div>
                        </div>

                        <div class="expanded-content">
                            <div class="column-title">
                                <h3>
                                    <span class="column-name">{{ $column['label'] }}</span>
                                    <span class="task-count" data-kanban-count="{{ $column['state'] }}">{{ count($column['tasks']) }}</span>
                                </h3>
                                <x-vuero.layouts.kanban-column-menu :state="$column['state']" :label="$column['label']" />
                            </div>

                            <div
                                class="kanban-board"
                                data-kanban-board="{{ $column['state'] }}"
                                data-kanban-droppable="{{ $column['droppable'] ? 'true' : 'false' }}"
                            >
                                @if (count($column['tasks']) === 0)
                                    <div class="kanban-empty">
                                        <img class="empty-state light-image-block" src="{{ $column['empty']['light'] }}" alt="">
                                        <img class="empty-state dark-image-block" src="{{ $column['empty']['dark'] }}" alt="">
                                        <p class="empty-text">{!! $column['empty']['text'] !!}</p>
                                    </div>
                                @endif

                                @foreach ($column['tasks'] as $task)
                                    <article
                                        class="kanban-card {{ $column['state'] === 'new' ? 'is-new' : 'gelatine' }}"
                                        draggable="{{ $column['draggable'] ? 'true' : 'false' }}"
                                        data-kanban-task
                                        data-task-id="{{ $task['id'] }}"
                                        data-task-state="{{ $column['state'] }}"
                                        data-task-due="{{ $task['dueDate'] }}"
                                        data-search="{{ Illuminate\Support\Str::lower($task['title']) }}"
                                    >
                                        <div class="card-body">
                                            @if ($column['state'] === 'new')
                                                <h4 class="card-title">{{ $task['title'] }}</h4>
                                                <div class="new-avatar">
                                                    @php $owner = $task['participants'][0]; @endphp
                                                    <div @class(['avatar-wrapper', $owner['color'] ? 'is-'.$owner['color'] : null])>
                                                        <img class="task-owner" src="{{ $owner['picture'] }}" alt="">
                                                    </div>
                                                </div>
                                            @else
                                                <h4 class="card-title">{{ $task['title'] }}</h4>
                                                <div class="kanban-card-stats">
                                                    <span><x-vuero.icon name="clock" size="14" /> {{ $task['dueDate'] }}</span>
                                                    <x-vuero.layouts.flex-avatar-stack :avatars="$task['participants']" :limit="3" />
                                                </div>
                                            @endif
                                        </div>
                                        @if ($column['state'] === 'new')
                                            <footer class="card-footer">
                                                <button type="button" class="card-footer-item">View</button>
                                                <button type="button" class="card-footer-item" data-kanban-approve="{{ $task['id'] }}">Approve</button>
                                            </footer>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-vuero.layouts.app>
