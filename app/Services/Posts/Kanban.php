<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Http\Requests\Posts\KanbanCommandRequest;
use App\Http\Requests\Posts\KanbanReadRequest;
use App\Models\ContentBoard;
use App\Models\User;
use App\Services\Editor\EditorDocuments;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\AdminAccess;
use App\Support\CalendarInput;
use App\Support\EditorRenderer;
use App\Support\KanbanFilterRegistry;
use App\Support\LocaleCatalog;
use App\Support\LocalizedDate;
use App\Support\PostWorkflowPermissions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use JobMetric\Flow\Exceptions\TaskRestrictionException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Exceptions\PostStatusTransitionNotAllowedException;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostContentRevision;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Models\PostWorkflowDefinition;
use JobMetric\Post\Services\PostStreamWorkflow;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use JobMetric\Post\Services\WorkflowTransitionAccess;
use JobMetric\Post\Services\WorkflowTransitionPreview;
use JobMetric\MultiCalendar\Factory\CalendarConverterFactory;
use Throwable;

/**
 * Serve private, independently paginated workflow columns and atomic shared ordering.
 */
class Kanban
{
    private ?ContentBoard $board = null;

    /** @var array<string, mixed> */
    private array $view = [];

    /** @var array<int, User|null> */
    private array $authors = [];


    /**
     * Select and authorize the custom board scope for this request.
     *
     * @param string $type
     * @param int|null $id
     * @param User $actor
     *
     * @return static
     * @throws Throwable
     */
    public function useBoard(string $type, ?int $id, User $actor): static
    {
        $this->board = $id ? app(ContentBoards::class)->accessible($type, $id, $actor) : null;

        return $this;
    }

    /**
     * Return the already authorized custom board, or null for the default board.
     *
     * @return ContentBoard|null
     */
    public function selectedBoard(): ?ContentBoard
    {
        return $this->board;
    }

    /**
     * Count cards authored by each user that this viewer can see on the selected board.
     *
     * @param string $type
     * @param User $actor
     *
     * @return array<int, int>
     */
    public function visibleAuthorCounts(string $type, User $actor): array
    {
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        if (! $definition) {
            return [];
        }
        $counts = [];
        foreach ($this->states($definition) as $state) {
            if (! empty($state['view_roles']) && ! app(PostStateAccess::class)->hasAnySystemRole($actor, $state['view_roles'])) {
                continue;
            }
            $query = $this->query($type, $state['status'])->where('ks.definition_id', $definition->id)
                ->selectSub($this->laneQuery($type, 'author'), 'member_author_id');
            foreach (DB::query()->fromSub($query, 'visible_cards')->selectRaw('member_author_id, COUNT(*) AS total')->groupBy('member_author_id')->get() as $row) {
                if ($row->member_author_id !== null) {
                    $id = (int) $row->member_author_id;
                    $counts[$id] = ($counts[$id] ?? 0) + (int) $row->total;
                }
            }
        }

        return $counts;
    }

    /**
     * Find a card through the same board, workflow and state restrictions as column reads.
     *
     * @param string $type
     * @param int $id
     * @param User $actor
     *
     * @return Post
     */
    public function visiblePost(string $type, int $id, User $actor): Post
    {
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        abort_unless($definition, 404);
        foreach ($this->states($definition) as $state) {
            if (! empty($state['view_roles']) && ! app(PostStateAccess::class)->hasAnySystemRole($actor, $state['view_roles'])) {
                continue;
            }
            $post = $this->query($type, $state['status'])->where('ks.definition_id', $definition->id)->where((new Post())->qualifyColumn('id'), $id)->first();
            if ($post) {
                return $post;
            }
        }
        abort(404);
    }

    /**
     * Summarize visible cards per workflow state without disclosing restricted columns.
     *
     * @param string $type
     * @param User $actor
     * @param array<string, mixed> $data
     *
     * @return Response
     */
    public function insights(string $type, User $actor, array $data = []): Response
    {
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        if (! $definition) return Response::make(true, '', ['states' => []]);
        $this->setView($type, $data, $actor);
        $options = app(KanbanWorkspace::class)->options($type, $this->board);
        $rows = [];
        foreach ($this->states($definition) as $state) {
            if (! empty($state['view_roles']) && ! app(PostStateAccess::class)->hasAnySystemRole($actor, $state['view_roles'])) continue;
            $query = $this->viewQuery($type, $state['status'], (int) $definition->id, false);
            $count = 0; $totalDays = 0; $stale = 0; $oldest = 0; $streamIds = [];
            foreach ($query->cursor() as $post) {
                $entered = $post->state_entered_at_raw ?? $post->stream_created_at ?? $post->created_at;
                $days = max(0, (int) Carbon::parse($entered)->diffInDays(now()));
                $count++; $totalDays += $days; $oldest = max($oldest, $days);
                $streamIds[] = (int) $post->publication_stream_id;
                if ($days >= $options['stale_days']) $stale++;
            }
            $arrived = 0; $departed = 0;
            foreach (array_chunk($streamIds, 500) as $ids) {
                $events = DB::table(config('post.tables.lifecycle_event', 'post_lifecycle_events'))
                    ->whereIn('stream_id', $ids)->where('event', 'transition')->where('created_at', '>=', now()->subDays(30))
                    ->get(['context']);
                foreach ($events as $event) {
                    $context = is_array($event->context) ? $event->context : json_decode($event->context ?: '{}', true);
                    if (($context['to'] ?? null) === $state['status']) $arrived++;
                    if (($context['from'] ?? null) === $state['status']) $departed++;
                }
            }
            $rows[] = ['status' => $state['status'], 'name' => $state['translations'][app()->getLocale()]['name'] ?? $state['name'],
                'count' => $count, 'average_days' => $count ? round($totalDays / $count, 1) : 0, 'stale' => $stale,
                'oldest_days' => $oldest, 'arrived_30d' => $arrived, 'departed_30d' => $departed];
        }

        return Response::make(true, '', ['states' => $rows]);
    }

    /**
     * Build the active language's calendar month and its visible card events.
     *
     * @param string $type
     * @param User $actor
     * @param int $offset
     * @param array<string, mixed> $data
     *
     * @return Response
     */
    public function calendar(string $type, User $actor, int $offset = 0, array $data = []): Response
    {
        $converter = CalendarConverterFactory::make(LocaleCatalog::calendar());
        $today = now();
        [$year, $month] = array_slice($converter->fromGregorian($today->year, $today->month, $today->day), 0, 2);
        $month += $offset;
        while ($month < 1) { $month += 12; $year--; }
        while ($month > 12) { $month -= 12; $year++; }
        [$gy, $gm, $gd] = $converter->toGregorian($year, $month, 1);
        $start = Carbon::create($gy, $gm, $gd, 0, 0, 0, config('app.timezone'));
        $nextMonth = $month === 12 ? 1 : $month + 1;
        $nextYear = $month === 12 ? $year + 1 : $year;
        [$ey, $em, $ed] = $converter->toGregorian($nextYear, $nextMonth, 1);
        $end = Carbon::create($ey, $em, $ed, 0, 0, 0, config('app.timezone'));
        $days = [];
        for ($day = $start->copy(); $day->lt($end); $day->addDay()) {
            $days[] = ['date' => $day->toDateString(), 'label' => LocalizedDate::format($day, false), 'weekday' => $day->dayOfWeek];
        }
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        $events = []; $truncated = false;
        if ($definition) {
            $this->setView($type, $data, $actor);
            foreach ($this->states($definition) as $state) {
                if (! empty($state['view_roles']) && ! app(PostStateAccess::class)->hasAnySystemRole($actor, $state['view_roles'])) continue;
                $scheduleTable = config('post.tables.schedule', 'post_scheduled_actions');
                $posts = $this->viewQuery($type, $state['status'], (int) $definition->id, false)
                    ->with('translations')->leftJoin('kanban_card_meta as card_work', (new Post())->qualifyColumn('id'), '=', 'card_work.post_id')
                    ->addSelect('card_work.due_at as editorial_due_at')
                    ->where(function ($dates) use ($start, $end, $scheduleTable): void {
                        $dates->where(fn ($due) => $due->where('card_work.due_at', '>=', $start)->where('card_work.due_at', '<', $end))
                            ->orWhereExists(fn ($scheduled) => $scheduled->selectRaw('1')->from($scheduleTable.' as cs')
                                ->whereColumn('cs.stream_id', 'ks.id')->where('cs.state', 'pending')
                                ->where('cs.due_at', '>=', $start)->where('cs.due_at', '<', $end));
                    })->limit(501)->get();
                if ($posts->count() > 500) $truncated = true;
                $visiblePosts = $posts->take(500);
                $schedules = DB::table($scheduleTable)->whereIn('stream_id', $visiblePosts->pluck('publication_stream_id')->all())
                    ->where('state', 'pending')->where('due_at', '>=', $start)->where('due_at', '<', $end)
                    ->get(['stream_id', 'due_at'])->groupBy('stream_id');
                foreach ($visiblePosts as $post) {
                    $title = $post->getTranslation('title', LocaleCatalog::resolve(app()->getLocale())) ?: __('kanban.untitled');
                    $stateName = $state['translations'][app()->getLocale()]['name'] ?? $state['name'];
                    if ($post->editorial_due_at) {
                        $events[] = ['id' => (int) $post->id, 'date' => Carbon::parse($post->editorial_due_at)->toDateString(),
                            'kind' => 'deadline', 'title' => $title, 'state' => $stateName];
                    }
                    foreach ($schedules->get($post->publication_stream_id, []) as $scheduled) {
                        $events[] = ['id' => (int) $post->id, 'date' => Carbon::parse($scheduled->due_at)->toDateString(),
                            'kind' => 'publish', 'title' => $title, 'state' => $stateName];
                    }
                }
            }
        }
        return Response::make(true, '', ['month' => implode('/', array_slice(explode('/', LocalizedDate::format($start, false)), 0, 2)),
            'today' => $today->toDateString(), 'days' => $days, 'events' => $events, 'truncated' => $truncated, 'offset' => $offset]);
    }

    /**
     * Resolve the owning column table for this board.
     *
     * @return string
     */
    private function columnTable(): string
    {
        return $this->board ? 'content_board_columns' : 'post_kanban_columns';
    }

    /**
     * Resolve the owning rank table for this board.
     *
     * @return string
     */
    private function positionTable(): string
    {
        return $this->board ? 'content_board_positions' : 'post_kanban_positions';
    }

    /**
     * Scope column lookup to the exact board and type.
     *
     * @param string $type
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function columnQuery(string $type): \Illuminate\Database\Query\Builder
    {
        $query = DB::table($this->columnTable())->where('type', $type);
        if ($this->board) {
            $query->where('board_id', $this->board->id);
        }

        return $query;
    }

    /**
     * Preserve legacy personal preferences while isolating custom board sizes.
     *
     * @param string $type
     *
     * @return string
     */
    private function preferenceKey(string $type): string
    {
        return $this->board ? $type.':board:'.$this->board->id : $type;
    }

    /**
     * Validate selected states and drivers before reading any card data.
     *
     * @param PostWorkflowDefinition $definition
     *
     * @return array<int, array<string, mixed>>
     * @throws Throwable
     */
    private function states(PostWorkflowDefinition $definition): array
    {
        $states = collect($definition->graph['states'])->keyBy('status');
        if (! $this->board) {
            return $states->values()->all();
        }
        abort_if(array_diff($this->board->states, $states->keys()->all()) !== [], 422, __('kanban.invalid_states'));
        app(KanbanFilterRegistry::class)->validate($this->board->type, $this->board->filters);

        return array_map(fn ($status) => $states[$status], $this->board->states);
    }

    /**
     * Reject requests based on an older custom board configuration.
     *
     * @param array<string, mixed> $data
     *
     * @return void
     * @throws Throwable
     */
    public function checkVersion(array $data): void
    {
        if ($this->board) {
            abort_if((int) ($data['board_version'] ?? 0) !== $this->board->version, 409, __('kanban.stale'));
        }
    }

    /**
     * Read page metadata without exposing inaccessible post data.
     *
     * @param string $type
     * @param User $actor
     *
     * @return array<string, mixed>
     */
    public function page(string $type, User $actor): array
    {
        return ['workspace' => app(KanbanWorkspace::class)->preferences($type, $this->board, $actor),'options' => app(KanbanWorkspace::class)->options($type, $this->board),'filterCatalog' => app(KanbanFilterRegistry::class)->catalog($type),'groupChoices' => $this->groupChoices($type),'realtime' => app(\App\Support\KanbanRealtime::class)->client($type),'boardVersion' => $this->board?->version, 'boardName' => $this->board?->name, 'pageSize' => $this->pageSize($type, $actor), 'editable' => $this->mayMove($type, $actor), 'calendar' => LocaleCatalog::calendar(),
            'copy' => trans('kanban'), 'dataUrl' => route($this->board ? 'admin.posts.kanban.board.data' : 'admin.posts.kanban.data', ['type' => $type, ...($this->board ? ['board' => $this->board->id] : [])]),
            'membersUrl' => route($this->board ? 'admin.posts.kanban.board.members' : 'admin.posts.kanban.members', ['type' => $type, ...($this->board ? ['board' => $this->board->id] : [])]),
            'calendarUrl' => route($this->board ? 'admin.posts.kanban.board.calendar' : 'admin.posts.kanban.calendar', ['type' => $type, ...($this->board ? ['board' => $this->board->id] : [])]),
            'insightsUrl' => route($this->board ? 'admin.posts.kanban.board.insights' : 'admin.posts.kanban.insights', ['type' => $type, ...($this->board ? ['board' => $this->board->id] : [])]),
            'notificationsUrl' => route($this->board ? 'admin.posts.kanban.board.notifications' : 'admin.posts.kanban.notifications', ['type' => $type, ...($this->board ? ['board' => $this->board->id] : [])]),
            'commandUrl' => route($this->board ? 'admin.posts.kanban.board.command' : 'admin.posts.kanban.command', ['type' => $type, ...($this->board ? ['board' => $this->board->id] : [])]),
            'detailUrl' => route($this->board ? 'admin.posts.kanban.board.details' : 'admin.posts.kanban.details', ['type' => $type, 'post' => '__ID__', ...($this->board ? ['board' => $this->board->id] : [])]),
            'backUrl' => route(PostTypeRegistry::for($type)->hasMultipleKanban() ? 'admin.posts.kanban.index' : 'admin.posts.index', ['type' => $type]),
            'typeLabel' => __(PostTypeRegistry::for($type)->getLabel()),
            'locales' => collect(LocaleCatalog::all())->map(fn ($language, $code) => ['code' => $code, 'name' => $language['name']])->values()->all()];
    }

    /**
     * Read initial pages or one bounded state page using an encrypted versioned cursor.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws Throwable
     */
    public function read(string $type, array $data, User $actor): Response
    {
        $data = dto($data, KanbanReadRequest::class);
        if (isset($data['board_version'])) {
            $this->checkVersion($data);
        }
        $this->setView($type, $data, $actor);
        $group = $data['group_by'] ?? app(KanbanWorkspace::class)->preferences($type, $this->board, $actor)['group_by'];
        abort_unless(array_key_exists($group, $this->groupChoices($type)), 422);
        $this->view['group_by'] = $group;
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        if (! $definition) {
            return Response::make(true, '', ['definition_id' => null, 'columns' => [], 'summary'=>['total'=>0,'scheduled'=>0,'stale'=>0], 'options'=>app(KanbanWorkspace::class)->options($type,$this->board)]);
        }
        abort_if(isset($data['definition_id']) && (int) $data['definition_id'] !== (int) $definition->id, 409, __('kanban.stale'));
        $options = app(KanbanWorkspace::class)->options($type, $this->board);
        $states = $this->states($definition);
        $summary = ['total' => 0,'scheduled' => 0,'stale' => 0];
        $laneBase = null;
        foreach ($states as $state) {
            if (!empty($state['view_roles']) && !app(PostStateAccess::class)->hasAnySystemRole($actor, $state['view_roles'])) {
                continue;
            }
            $query = $this->viewQuery($type, $state['status'], $definition->id, false, false);
            $summary['total'] += (clone $query)->count();
            $summary['scheduled'] += app(KanbanWorkspace::class)->filter(clone $query, $type, ['summary' => 'scheduled'], $options['stale_days'])->count();
            $summary['stale'] += app(KanbanWorkspace::class)->filter(clone $query, $type, ['summary' => 'stale'], $options['stale_days'])->count();
            $ids = (clone $query)->select((new Post())->qualifyColumn('id'));
            $laneBase = $laneBase ? $laneBase->union($ids) : $ids;
        }
        $lanes = [['id' => 'all','name' => '']];
        $moreLanes = false;
        if ($group !== 'none') {
            $base = Post::query()->join((new PostPublicationStream())->getTable().' as ks', fn ($join) => $join->on('ks.post_id', '=', (new Post())->qualifyColumn('id'))->where('ks.locale', '*'));
            if ($laneBase) {
                $base->whereIn((new Post())->qualifyColumn('id'), $laneBase);
            } else {
                $base->whereRaw('1=0');
            }
            $sub = $this->laneQuery($type, $group);
            $base->select([])->selectSub($sub, 'lane_id')->distinct()->orderBy('lane_id')->offset((int)($data['lane_offset'] ?? 0))->limit(21);
            $values = $base->get();
            $moreLanes = $values->count() > 20;
            $lanes = $values->take(20)->map(function ($row) use ($group): array {
                $id = $row->lane_id;
                $name = $id === null ? __('kanban.ungrouped') : ($group === 'author' ? User::find($id)?->name : \JobMetric\Taxonomy\Models\Taxonomy::find($id)?->getTranslation('name', LocaleCatalog::resolve(app()->getLocale())));
                return ['id' => $id === null ? 'none' : (string)$id,'name' => $name ?: __('kanban.ungrouped')];
            })->values()->all();
        }
        if ($lanes === [] && $group !== 'none') {
            $lanes = [['id' => 'none','name' => __('kanban.ungrouped')]];
        }
        if (isset($data['lane'])) {
            $lanes = array_values(array_filter($lanes, fn ($lane) => $lane['id'] === $data['lane']));
        }
        $columns = [];
        foreach ($lanes as $lane) {
            foreach ($states as $state) {
                if (isset($data['state']) && $data['state'] !== $state['status']) {
                    continue;
                }
                $this->view['lane'] = $lane['id'];
                $column = $this->column($type, $state, $definition, $actor, $data['cursor'] ?? null);
                $column['lane'] = $lane['id'];
                $column['lane_name'] = $lane['name'];
                $column['lane_offset'] = (int)($data['lane_offset'] ?? 0);
                $column['capacity'] = collect($options['limits'])->firstWhere('state', $state['status']);
                $column['capacity_count'] = $column['restricted'] ? null : $this->query($type, $state['status'])->where('ks.definition_id', $definition->id)->count();
                $columns[] = $column;
            }
        }
        abort_if(isset($data['state']) && $columns === [], 404);
        return Response::make(true, '', ['board_version' => $this->board?->version,'board_name' => $this->board?->name,'definition_id' => $definition->id,'columns' => $columns,'summary' => $summary,'options' => $options,'lanes' => $lanes,'next_lane_offset' => $moreLanes ? (int)($data['lane_offset'] ?? 0) + 20 : null]);
    }

    /**
     * Describe supported grouping keys using the registered taxonomy collections.
     *
     * @param string $type
     *
     * @return array<string, string>
     */
    private function groupChoices(string $type): array
    {
        $choices = ['none' => __('kanban.group_none'),'author' => __('kanban.author')];
        foreach (PostTypeRegistry::for($type)->getTaxonomyTypes() as $key => $definition) {
            $choices['taxonomy:'.$key] = __(\JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($definition['type'])->getLabel());
        }
        return $choices;
    }

    /**
     * Build a deterministic scalar grouping expression without duplicating multi-term cards.
     *
     * @param string $type
     * @param string $group
     *
     * @return \Illuminate\Database\Query\Builder
     */
    private function laneQuery(string $type, string $group): \Illuminate\Database\Query\Builder
    {
        if ($group === 'author') {
            return DB::table((new PostContentRevision())->getTable())->select('created_by')->whereColumn('stream_id', 'ks.id')->orderBy('id')->limit(1);
        }
        return DB::table(config('taxonomy.tables.taxonomy_relation'))->selectRaw('MIN(taxonomy_id)')->whereColumn('taxonomizable_id', (new Post())->qualifyColumn('id'))->where('taxonomizable_type', (new Post())->getMorphClass())->where('collection', substr($group, 9));
    }

    /**
     * Apply temporary view restrictions while preserving the canonical rank query.
     *
     * @param string $type
     * @param string $status
     * @param int $definition
     * @param bool $lane
     * @param bool $summary
     *
     * @return Builder<Post>
     * @throws ValidationException
     */
    private function viewQuery(string $type, string $status, int $definition, bool $lane = true, bool $summary = true): Builder
    {
        $options = app(KanbanWorkspace::class)->options($type, $this->board);
        $query = app(KanbanWorkspace::class)->filter($this->query($type, $status)->where('ks.definition_id', $definition), $type, array_diff_key($this->view, ['summary' => true]), $options['stale_days']);
        if (! empty($this->view['assignee_id'])) {
            $query->whereExists(fn ($assigned) => $assigned->selectRaw('1')->from('kanban_card_meta as work')->whereColumn('work.post_id', (new Post())->qualifyColumn('id'))->where('work.assignee_id', $this->view['assignee_id']));
        }
        if ($summary && !empty($this->view['summary'])) {
            app(KanbanWorkspace::class)->filter($query, $type, ['summary' => $this->view['summary']], $options['stale_days']);
        }
        $group = $this->view['group_by'] ?? 'none';
        if ($lane && $group !== 'none') {
            $sub = $this->laneQuery($type, $group);
            $value = $this->view['lane'] ?? 'none';
            $query->whereRaw('('.$sub->toSql().')'.($value === 'none' ? ' IS NULL' : ' = ?'), [...$sub->getBindings(),...($value === 'none' ? [] : [(int)$value])]);
        }
        return app(KanbanWorkspace::class)->metadata($query);
    }

    /**
     * Normalize temporary board filters for columns, calendar and insights.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return void
     */
    private function setView(string $type, array $data, User $actor): void
    {
        $this->view = array_intersect_key($data, array_flip(['search', 'filters', 'summary', 'group_by', 'lane']));
        if (! empty($data['mine'])) {
            $this->view['assignee_id'] = (int) $actor->id;
        } elseif (! empty($data['assignee_id'])) {
            $this->view['assignee_id'] = (int) $data['assignee_id'];
        }
        if (! empty($data['filters'])) {
            $this->view['filters'] = app(KanbanFilterRegistry::class)->validate($type, $data['filters']);
        }
    }

    /**
     * Bind cursors to the normalized view regardless of query parameter ordering.
     *
     * @return string
     * @throws \JsonException
     */
    private function viewFingerprint(): string
    {
        $view = $this->view;
        ksort($view);
        return hash('sha256', json_encode($view, JSON_THROW_ON_ERROR));
    }

    /**
     * Read only the current actor's page size for one registered type.
     *
     * @param string $type
     * @param User $actor
     *
     * @return int
     */
    private function pageSize(string $type, User $actor): int
    {
        $size = (int) ($actor->ui_preferences['kanban'][$this->preferenceKey($type)]['page_size'] ?? 5);

        return in_array($size, [5, 10, 20, 50], true) ? $size : 5;
    }

    /**
     * Resolve management through the shared Rolix parent and deny rules.
     *
     * @param string $type
     * @param User $actor
     *
     * @return bool
     */
    private function mayMove(string $type, User $actor): bool
    {
        return app(AdminAccess::class)->allows($actor, app(PostWorkflowPermissions::class)->for($type)['manage']);
    }

    /**
     * Build the SQL ordering shared by pagination and movement validation.
     *
     * @param string $type
     * @param string $status
     *
     * @return Builder<Post>
     */
    private function query(string $type, string $status): Builder
    {
        $posts = (new Post())->getTable();
        $streams = (new PostPublicationStream())->getTable();
        $query = Post::query()->join($streams.' as ks', function ($join) use ($posts): void {
            $join->on('ks.post_id', '=', $posts.'.id')->where('ks.locale', PostPublicationStream::SHARED);
        })->leftJoin($this->columnTable().' as kc', function ($join) use ($type, $status): void {
            $join->where('kc.type', $type)->where('kc.status', $status);
            if ($this->board) {
                $join->where('kc.board_id', $this->board->id);
            }
        })->leftJoin($this->positionTable().' as kp', function ($join) use ($posts): void {
            $join->on('kp.post_id', '=', $posts.'.id')->on('kp.column_id', '=', 'kc.id');
        })->where($posts.'.type', $type)->where('ks.status', $status)
            ->select($posts.'.*', 'ks.id as publication_stream_id', 'ks.version as stream_version', 'ks.draft_revision_id', 'ks.approved_revision_id', 'ks.status as workflow_status')
            ->selectRaw('COALESCE(kp.rank, '.$posts.'.id * 1024) as board_rank');
        if ($this->board) {
            app(KanbanFilterRegistry::class)->apply($query, $type, $this->board->filters);
        }

        return $query;
    }

    /**
     * Read a column without leaking its card count when state roles deny visibility.
     *
     * @param string $type
     * @param array<string, mixed> $state
     * @param PostWorkflowDefinition $definition
     * @param User $actor
     * @param string|null $cursor
     *
     * @return array<string, mixed>
     * @throws Throwable
     */
    private function column(string $type, array $state, PostWorkflowDefinition $definition, User $actor, ?string $cursor): array
    {
        $visible = empty($state['view_roles']) || app(PostStateAccess::class)->hasAnySystemRole($actor, $state['view_roles']);
        $version = (int) $this->columnQuery($type)->where('status', $state['status'])->value('version');
        $column = ['id' => $state['id'], 'status' => $state['status'], 'name' => $state['translations'][app()->getLocale()]['name'] ?? $state['name'],
            'restricted' => ! $visible, 'version' => $version, 'count' => null, 'items' => [], 'cursor' => null];
        if (! $visible) {
            return $column;
        }
        $query = $this->viewQuery($type, $state['status'], (int)$definition->id);
        $column['count'] = (clone $query)->count();
        if ($cursor) {
            try {
                $after = json_decode(Crypt::decryptString($cursor), true, flags: JSON_THROW_ON_ERROR);
            } catch (Throwable) {
                abort(422, __('kanban.invalid_cursor'));
            }
            abort_unless(($after['type'] ?? null) === $type && ($after['state'] ?? null) === $state['status']
                && ($after['actor'] ?? null) === (int) $actor->id && ($after['board'] ?? null) === $this->board?->id && ($after['view'] ?? null) === $this->viewFingerprint(), 422);
            abort_if((int) ($after['board_version'] ?? 0) !== ($this->board?->version ?? 0), 409, __('kanban.stale'));
            abort_if((int) ($after['definition'] ?? 0) !== (int) $definition->id || (int) ($after['version'] ?? -1) !== $version, 409, __('kanban.stale'));
            $posts = (new Post())->getTable();
            $query->where(function ($q) use ($after, $posts): void {
                $rank = 'COALESCE(kp.rank, '.$posts.'.id * 1024)';
                $q->whereRaw($rank.' > ?', [$after['rank']])->orWhere(fn ($same) => $same->whereRaw($rank.' = ?', [$after['rank']])->where($posts.'.id', '>', $after['id']));
            });
        }
        $size = $this->pageSize($type, $actor);
        $rows = $query->with(['translations','taxonomies.translations','files'])->orderBy('board_rank')->orderBy((new Post())->qualifyColumn('id'))->limit($size + 1)->get();
        $more = $rows->count() > $size;
        $page = $rows->take($size);
        $column['items'] = $page->map(fn (Post $post) => $this->card($post))->values()->all();
        if ($more && ($last = $page->last())) {
            $column['cursor'] = Crypt::encryptString(json_encode([
                'view' => $this->viewFingerprint(), 'board' => $this->board?->id, 'board_version' => $this->board?->version, 'type' => $type, 'state' => $state['status'], 'actor' => (int) $actor->id, 'definition' => (int) $definition->id,
                'version' => $version, 'rank' => (int) $last->board_rank, 'id' => (int) $last->id,
            ], JSON_THROW_ON_ERROR));
        }

        return $column;
    }

    /**
     * Present safe human-facing card fields from the shared draft.
     *
     * @param Post $post
     *
     * @return array<string, mixed>
     */
    private function card(Post $post): array
    {
        $author = $post->board_author_id ?? PostContentRevision::where('stream_id', $post->publication_stream_id)->orderBy('id')->value('created_by');
        if ($author && !array_key_exists((int)$author, $this->authors)) {
            $this->authors[(int)$author] = User::find($author);
        }
        $user = $author ? ($this->authors[(int)$author] ?? null) : null;
        $entered = $post->state_entered_at_raw ?? $post->state_entered_at ?? DB::table(config('post.tables.lifecycle_event', 'post_lifecycle_events'))->where('stream_id', $post->publication_stream_id)->whereIn('event', ['transition','workflow_migrated'])->whereColumn('context->from', '!=', 'context->to')->max('created_at');
        $entered = $entered ?? $post->stream_created_at ?? DB::table((new PostPublicationStream())->getTable())->where('id', $post->publication_stream_id)->value('created_at') ?? $post->created_at;
        $age = max(0, (int)Carbon::parse($entered)->diffInDays(now()));
        $post->loadMissing(['taxonomies.translations','files']);
        $image = $post->files->first(fn ($file) => $file->pivot?->collection === 'base');
        $terms = $post->taxonomies->map(fn ($term) => ['id' => (int)$term->id,'name' => $term->getTranslation('name', LocaleCatalog::resolve(app()->getLocale())) ?: '#'.$term->id,'collection' => $term->pivot->collection])->values()->all();
        $schedule = DB::table(config('post.tables.schedule', 'post_scheduled_actions'))->where('stream_id', $post->publication_stream_id)->where('state', 'pending')->orderBy('due_at')->value('due_at');
        $work = app(KanbanCardWork::class)->read((int) $post->id, $this, request()->user());
        $reviews = DB::table('kanban_card_reviews')->where('post_id', $post->id)->where('revision_id', $post->draft_revision_id)->pluck('status');
        $reviewStatus = (int) ($post->approved_revision_id ?? 0) > 0 && (int) $post->approved_revision_id === (int) $post->draft_revision_id
            ? 'approved' : ($reviews->contains('rejected') ? 'rejected' : ($reviews->contains('pending') ? 'pending' : null));
        return ['id' => (int)$post->id,'title' => $post->getTranslation('title', LocaleCatalog::resolve(app()->getLocale())) ?: __('kanban.untitled'),
            'author' => $user?->name ?? __('kanban.unknown_author'),'author_id' => $author ? (int)$author : null,'avatar_url' => $user?->avatarUrl(),
            'image_url' => $image ? (\JobMetric\Media\Http\Resources\MediaResource::make($image)->resolve(request())['src'] ?? null) : null,
            'terms' => $terms,'age_days' => $age,'entered_at' => LocalizedDate::format($entered),'scheduled_at' => $schedule ? LocalizedDate::format($schedule) : null,
            'updated_at' => LocalizedDate::format($post->updated_at),'version' => (int)$post->stream_version,'revision_id' => (int)$post->draft_revision_id,'state' => $post->workflow_status,
            'assignee' => $work['assignee'], 'due_at' => $work['due_at'], 'due_state' => $work['due_state'],
            'priority' => $work['priority'], 'review_status' => $reviewStatus,
            'dependency_count' => count($work['dependencies'])];
    }

    /**
     * Read details and evaluate destinations without invoking actions.
     *
     * @param string $type
     * @param int $id
     * @param User $actor
     * @param string|null $locale
     * @param int|null $commentCursor
     *
     * @return Response
     * @throws Throwable
     */
    public function details(string $type, int $id, User $actor, ?string $locale = null, ?int $commentCursor = null): Response
    {
        $locale = LocaleCatalog::resolve($locale ?? app()->getLocale());
        $post = Post::where('type', $type)->with('translations')->findOrFail($id);
        abort_unless(app(PostStateAccess::class)->allows($post, $actor), 403);
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        $stream = PostPublicationStream::where('post_id', $id)->where('locale', '*')->firstOrFail();
        abort_unless($definition && (int) $stream->definition_id === (int) $definition->id, 409, __('kanban.stale'));
        if ($this->board) {
            $this->states($definition);
            abort_unless(in_array($stream->status, $this->board->states, true) && $this->query($type, $stream->status)->where((new Post())->qualifyColumn('id'), $id)->exists(), 404);
        }
        $post->publication_stream_id = $stream->id;
        $post->stream_version = $stream->version;
        $post->draft_revision_id = $stream->draft_revision_id;
        $post->approved_revision_id = $stream->approved_revision_id;
        $post->workflow_status = $stream->status;
        $states = collect($definition->graph['states'])->keyBy('id');
        $destinations = [];
        if ($this->mayMove($type, $actor)) {
            foreach ($definition->graph['transitions'] as $edge) {
                if (($states[$edge['from']]['status'] ?? null) !== $stream->status) {
                    continue;
                }
                $preview = app(WorkflowTransitionPreview::class)->inspect($edge, $stream, $actor);
                $target = $states[$edge['to']];
                if (($target['content_status'] ?? '') === 'publish'
                    && ! KanbanCardWork::dependenciesReady((int) $post->id, $type)) {
                    $preview['status'] = 'blocked';
                    $preview['checks'][] = ['message' => __('kanban.dependency_blocked')];
                }
                if ($this->board && ! in_array($target['status'], $this->board->states, true)) {
                    continue;
                }
                $destinations[] = ['transition' => $edge['slug'], 'label' => $edge['label'], 'state' => $target['status'],
                    'status' => $preview['status'], 'checks' => $preview['checks'], 'requires_due' => ($target['content_status'] ?? '') === 'future'];
            }
        }
        $base = $type === 'page' ? 'admin.content.pages.manage' : 'admin.content.posts.manage';
        $mayEdit = app(AdminAccess::class)->allows($actor, PostTypeRegistry::for($type)->getManagePermission() ?? $base);

        return Response::make(true, '', ['card' => $this->card($post), 'definition_id' => $definition->id,
            'locale' => $locale, 'excerpt' => $post->getTranslation('excerpt', $locale),
            'content_html' => app(EditorRenderer::class)->render(app(EditorDocuments::class)->readForEditor($post, $locale)), 'destinations' => $destinations,
            'edit_url' => $mayEdit ? route('admin.posts.edit', ['type' => $type, 'post' => $id, 'embedded' => 'kanban']) : null,
            'history_url' => $mayEdit ? route('admin.posts.history', ['type' => $type, 'post' => $id]) : null,
            'work' => app(KanbanCardWork::class)->read($id, $this, $actor),
            'discussion' => app(KanbanDiscussion::class)->read($id, $commentCursor),
            'review' => app(KanbanReview::class)->read($post, $stream, (bool) (collect($definition->graph['states'])->firstWhere('status', $stream->status)['allows_approval'] ?? false), $actor)]);
    }

    /**
     * Persist preferences or perform one version-checked atomic movement.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws Throwable
     */
    public function command(string $type, array $data, User $actor): Response
    {
        if (($data['operation'] ?? '') === 'bulk') {
            return $this->bulk($type, $data, $actor);
        }
        if (! empty($data['due_local'])) {
            $data['due_at'] = CalendarInput::parse($data['due_local']);
        }
        $data = dto($data, KanbanCommandRequest::class);
        if ($data['operation'] !== 'card_work' && ! empty($data['due_at']) && Carbon::parse($data['due_at'])->lte(now())) {
            throw ValidationException::withMessages(['due_at' => __('kanban.due_error')]);
        }
        $this->checkVersion($data);
        if (isset($data['group_by'])) {
            abort_unless(array_key_exists($data['group_by'], $this->groupChoices($type)), 422);
        }
        if ($data['operation'] === 'limits') {
            abort_unless($this->mayMove($type, $actor), 403);
            $definition = app(PostLifecycleView::class)->activeDefinition($type);
            abort_unless($definition, 422);
            $options = app(KanbanWorkspace::class)->saveOptions($type, $this->board, $data, $actor);
            app(\App\Support\KanbanRealtime::class)->changed($type);
            return Response::make(true, __('kanban.saved'), ['options' => $options]);
        }
        if ($data['operation'] === 'settings') {
            DB::transaction(function () use ($type, $data, $actor): void {
                if ($this->board) {
                    app(PostWorkflowDefinitions::class)->lock($type);
                    $this->board = ContentBoard::whereKey($this->board->id)->lockForUpdate()->firstOrFail();
                    $this->checkVersion($data);
                    app(ContentBoards::class)->accessible($type, $this->board->id, $actor);
                }
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                $context = ['type' => $type, 'user_id' => (int) $actor->id, ...($this->board ? ['id' => (int)$this->board->id] : [])];
                Hooks::lifecycle('jobic.posts.kanban.settings.saving', $context);
                $preferences = $user->ui_preferences ?? [];
                $preferences['kanban'][$this->preferenceKey($type)]['page_size'] = (int) $data['page_size'];
                foreach (['density','card_fields','group_by'] as $key) {
                    if (array_key_exists($key, $data)) {
                        $preferences['kanban'][$this->preferenceKey($type)][$key] = $data[$key];
                    }
                }
                if (array_key_exists('card_fields', $data)) {
                    $preferences['kanban'][$this->preferenceKey($type)]['card_fields_version'] = 2;
                }
                $user->ui_preferences = $preferences;
                $user->save();
                DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.settings.saved', $context));
            });

            return Response::make(true, __('kanban.saved'), ['page_size' => (int) $data['page_size']]);
        }
        if ($data['operation'] === 'card_work') {
            abort_unless($this->mayMove($type, $actor), 403);
            return app(KanbanCardWork::class)->save($type, $this, $data, $actor);
        }
        if ($data['operation'] === 'comment') {
            return app(KanbanDiscussion::class)->add($type, $this, $data, $actor);
        }
        if ($data['operation'] === 'notification_read') {
            return app(KanbanNotifications::class)->markRead($type, $this, $actor, (int) $data['notification_id']);
        }
        if ($data['operation'] === 'notification_preferences') {
            return app(KanbanNotifications::class)->savePreferences($actor, $data['notification_preferences']);
        }
        if ($data['operation'] === 'request_review') {
            abort_unless($this->mayMove($type, $actor), 403);
            return app(KanbanReview::class)->request($type, $this, $data, $actor);
        }
        if ($data['operation'] === 'review_decide') {
            abort_unless($this->mayMove($type, $actor), 403);
            return app(KanbanReview::class)->decide($type, $this, $data, $actor);
        }
        abort_unless($this->mayMove($type, $actor), 403);
        $id = (int) $data['post_id'];
        $post = Post::where('type', $type)->findOrFail($id);
        abort_unless(app(PostStateAccess::class)->allows($post, $actor), 403);
        $locks = app(TaxonomyEditLocks::class);
        $existing = $locks->inspect('posts:'.$type, $id);
        $lease = $locks->acquire('posts:'.$type, $id, $actor);
        abort_unless($lease['acquired'], 423, __('kanban.locked'));
        try {
            DB::transaction(function () use ($type, $id, $data, $actor, $post): void {
                app(PostWorkflowDefinitions::class)->lock($type);
                if ($this->board) {
                    $this->board = ContentBoard::whereKey($this->board->id)->lockForUpdate()->firstOrFail();
                    $this->checkVersion($data);
                    app(ContentBoards::class)->accessible($type, $this->board->id, $actor);
                }
                $post = Post::where('type', $type)->whereKey($id)->lockForUpdate()->firstOrFail();
                abort_unless(app(PostStateAccess::class)->allows($post, $actor), 403);
                $definition = app(PostLifecycleView::class)->activeDefinition($type);
                abort_unless($definition && (int) $definition->id === (int) $data['definition_id'], 409, __('kanban.stale'));
                $stream = PostPublicationStream::where('post_id', $id)->where('locale', '*')->lockForUpdate()->firstOrFail();
                abort_if((int) $stream->version !== (int) $data['version'] || (int) $stream->draft_revision_id !== (int) $data['revision_id'], 409, __('kanban.stale'));
                $states = collect($definition->graph['states'])->keyBy('status');
                if ($this->board) {
                    $this->states($definition);
                    abort_unless(in_array($stream->status, $this->board->states, true) && in_array($data['state'], $this->board->states, true), 422);
                    abort_unless($this->query($type, $stream->status)->where((new Post())->qualifyColumn('id'), $id)->exists(), 404);
                }
                abort_unless($states->has($data['state']), 422);
                // Serialize all movements of a type, including cross-column moves, on its owning workflow row.
                DB::table(config('post.tables.workflow', 'post_workflows'))->where('type', $type)->lockForUpdate()->first();
                foreach (array_unique([$stream->status, $data['state']]) as $status) {
                    DB::table($this->columnTable())->insertOrIgnore(['type' => $type, 'status' => $status, 'version' => 0, ...($this->board ? ['board_id' => $this->board->id] : [])]);
                }
                $target = $this->columnQuery($type)->where('status', $data['state'])->lockForUpdate()->first();
                abort_if((int) $target->version !== (int) $data['column_version'], 409, __('kanban.stale'));
                if (isset($data['before_id'])) {
                    $targetRoles = $states[$data['state']]['view_roles'] ?? [];
                    abort_if($targetRoles !== [] && ! app(PostStateAccess::class)->hasAnySystemRole($actor, $targetRoles), 403);
                    abort_unless($this->query($type, $data['state'])->where((new Post())->qualifyColumn('id'), $data['before_id'])->exists(), 409, __('kanban.stale'));
                }
                if ($data['operation'] === 'move') {
                    $candidate = collect($definition->graph['transitions'])->firstWhere('slug', $data['transition']);
                    abort_unless($candidate && ($states[$data['state']]['id'] ?? null) === $candidate['to']
                        && ($states[$stream->status]['id'] ?? null) === $candidate['from'], 422);
                    abort_unless(app(WorkflowTransitionAccess::class)->allows($candidate, $actor), 403, trans('post::lifecycle.role_denied'));
                }
                $context = ['type' => $type, 'post_id' => $id, 'user_id' => (int) $actor->id, 'operation' => $data['operation'], 'id' => $this->board?->id];
                Hooks::lifecycle('jobic.posts.kanban.moving', $context);
                if ($data['operation'] === 'move') {
                    $source = $this->columnQuery($type)->where('status', $stream->status)->first();
                    abort_if((int) $source->version !== (int) $data['source_column_version'], 409, __('kanban.stale'));
                    $edge = collect($definition->graph['transitions'])->firstWhere('slug', $data['transition']);
                    abort_unless($edge && ($states[$data['state']]['id'] ?? null) === $edge['to'], 422);
                    app(PostStreamWorkflow::class)->transition(
                        $post,
                        '*',
                        $data['transition'],
                        (int) $data['revision_id'],
                        (int) $data['version'],
                        $actor,
                        empty($data['due_at']) ? null : Carbon::parse($data['due_at'])
                    );
                } else {
                    abort_unless($stream->status === $data['state'], 422);
                }
                $rank = $this->rankBefore($type, $data['state'], (int) $target->id, $id, isset($data['before_id']) ? (int) $data['before_id'] : null);
                DB::table($this->positionTable())->updateOrInsert(['post_id' => $id, ...($this->board ? ['column_id' => $target->id] : [])], ['column_id' => $target->id, 'rank' => $rank]);
                DB::table($this->columnTable())->where('id', $target->id)->increment('version');
                DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.moved', $context));
            });
        } catch (PostStatusTransitionNotAllowedException $error) {
            return Response::make(false, __('kanban.stale'), null, 409);
        } catch (TaskRestrictionException $error) {
            $status = $error->getMessage() === trans('post::lifecycle.role_denied') || $error->getMessage() === trans('post::lifecycle.permission_denied') ? 403 : 422;

            return Response::make(false, $error->getMessage(), null, $status);
        } finally {
            if (! $existing) {
                $locks->release('posts:'.$type, $id, (int) $actor->id, $lease['lock']['token']);
            }
        }

        app(\App\Support\KanbanRealtime::class)->changed($type);
        return Response::make(true, __('kanban.moved'), ['id' => $id]);
    }

    /**
     * Execute a bounded batch with independent savepoints and explicit per-card outcomes.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws Throwable
     */
    private function bulk(string $type, array $data, User $actor): Response
    {
        $data = dto($data, KanbanCommandRequest::class);
        $this->checkVersion($data);
        abort_unless($this->mayMove($type, $actor), 403);
        return DB::transaction(function () use ($type, $data, $actor): Response {
            app(PostWorkflowDefinitions::class)->lock($type);
            $versions = $this->columnQuery($type)->pluck('version', 'status')->all();
            $results = [];
            $seen = [];
            foreach ($data['items'] as $item) {
                $id = (int)($item['post_id'] ?? 0);
                try {
                    abort_if(isset($seen[$id]), 422);
                    $seen[$id] = true;
                    $item = dto($item + ['operation' => 'move','board_version' => $data['board_version'] ?? null], KanbanCommandRequest::class);
                    $stream = PostPublicationStream::where('post_id', $id)->where('locale', '*')->firstOrFail();
                    abort_if((int)($versions[$item['state']] ?? 0) !== (int)$item['column_version'] || (int)($versions[$stream->status] ?? 0) !== (int)$item['source_column_version'], 409, __('kanban.stale'));
                    $item['column_version'] = (int)$this->columnQuery($type)->where('status', $item['state'])->value('version');
                    $item['source_column_version'] = (int)$this->columnQuery($type)->where('status', $stream->status)->value('version');
                    $result = $this->command($type, $item, $actor);
                    $results[] = ['id' => $id,'ok' => $result->ok,'message' => $result->message,'status' => $result->status];
                } catch (Throwable $error) {
                    $status = $error instanceof ValidationException ? 422 : ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $error->getStatusCode() : ($error instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500));
                    $message = $error instanceof ValidationException ? implode(' ', array_merge(...array_values($error->errors()))) : ($status === 500 ? __('kanban.failed') : ($error->getMessage() ?: __('kanban.failed')));
                    $results[] = ['id' => $id,'ok' => false,'message' => $message,'status' => $status];
                }
            }
            return Response::make(true, __('kanban.bulk_done'), ['results' => $results]);
        });
    }

    /**
     * Allocate a rank by querying immediate neighbors; rebalance only when a gap is exhausted.
     *
     * @param string $type
     * @param string $status
     * @param int $columnId
     * @param int $postId
     * @param int|null $beforeId
     *
     * @return int
     */
    private function rankBefore(string $type, string $status, int $columnId, int $postId, ?int $beforeId): int
    {
        $table = (new Post())->getTable();
        $base = $this->query($type, $status)->where($table.'.id', '!=', $postId);
        $before = $beforeId ? (clone $base)->where($table.'.id', $beforeId)->first() : null;
        abort_if($beforeId && ! $before, 409, __('kanban.stale'));
        $rankSql = 'COALESCE(kp.rank, '.$table.'.id * 1024)';
        $previousQuery = clone $base;
        if ($before) {
            $previousQuery->where(function ($query) use ($before, $rankSql, $table): void {
                $query->whereRaw($rankSql.' < ?', [$before->board_rank])->orWhere(fn ($q) => $q->whereRaw($rankSql.' = ?', [$before->board_rank])->where($table.'.id', '<', $before->id));
            });
        }
        $previous = (int) ($previousQuery->orderByDesc('board_rank')->orderByDesc($table.'.id')->first()?->board_rank ?? 0);
        $next = $before ? (int) $before->board_rank : $previous + 2048;
        if ($next - $previous >= 2) {
            return $previous + intdiv($next - $previous, 2);
        }
        $index = 0;
        $beforeIndex = null;
        foreach ((clone $base)->orderBy('board_rank')->orderBy($table.'.id')->cursor() as $row) {
            if ($beforeId === (int) $row->id) {
                $beforeIndex = $index;
            }
            $index++;
            DB::table($this->positionTable())->updateOrInsert(['post_id' => $row->id, ...($this->board ? ['column_id' => $columnId] : [])], ['column_id' => $columnId, 'rank' => $index * 1024]);
        }

        return (($beforeIndex ?? $index) * 1024) + 512;
    }

    /**
     * Invalidate column cursors and ranks for transitions made through any entry point.
     *
     * @param PostPublicationStream $stream
     *
     * @return void
     */
    public static function streamChanged(PostPublicationStream $stream): void
    {
        if ($stream->locale !== '*' || ! $stream->wasChanged('status') || ! Schema::hasTable('post_kanban_columns') || ! Schema::hasTable('post_kanban_positions')) {
            return;
        }
        $post = $stream->post;
        if (! $post || ! PostTypeRegistry::for($post->type)->hasKanban()) {
            return;
        }
        $statuses = array_unique([$stream->getRawOriginal('status'), $stream->status]);
        foreach ($statuses as $status) {
            DB::table('post_kanban_columns')->insertOrIgnore(['type' => $post->type, 'status' => $status, 'version' => 0]);
        }
        $column = DB::table('post_kanban_columns')->where('type', $post->type)->where('status', $stream->status)->first();
        $last = (new self())->query($post->type, $stream->status)->where((new Post())->qualifyColumn('id'), '!=', $post->id)
            ->orderByDesc('board_rank')->orderByDesc((new Post())->qualifyColumn('id'))->first();
        DB::table('post_kanban_positions')->updateOrInsert(['post_id' => $post->id], ['column_id' => $column->id, 'rank' => (int) ($last?->board_rank ?? 0) + 1024]);
        DB::table('post_kanban_columns')->where('type', $post->type)->whereIn('status', $statuses)->increment('version');
        if (! Schema::hasTable('content_boards')) {
            return;
        }
        foreach (ContentBoard::where('type', $post->type)->cursor() as $board) {
            DB::table('content_board_columns')->where('board_id', $board->id)->whereIn('status', $statuses)->increment('version');
            $sourceColumns = DB::table('content_board_columns')->where('board_id', $board->id)->pluck('id');
            DB::table('content_board_positions')->whereIn('column_id', $sourceColumns)->where('post_id', $post->id)->delete();
            if (! in_array($stream->status, $board->states, true)) {
                continue;
            }
            DB::table('content_board_columns')->insertOrIgnore(['board_id' => $board->id, 'type' => $post->type, 'status' => $stream->status, 'version' => 0]);
            $column = DB::table('content_board_columns')->where('board_id', $board->id)->where('status', $stream->status)->first();
            $instance = new self();
            $instance->board = $board;
            try {
                $last = $instance->query($post->type, $stream->status)->where((new Post())->qualifyColumn('id'), '!=', $post->id)->orderByDesc('board_rank')->orderByDesc((new Post())->qualifyColumn('id'))->first();
            } catch (ValidationException) {
                continue;
            }
            DB::table('content_board_positions')->insert(['column_id' => $column->id, 'post_id' => $post->id, 'rank' => (int) ($last?->board_rank ?? 0) + 1024]);
        }
    }
}
