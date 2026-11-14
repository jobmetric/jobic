<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Models\ContentBoard;
use App\Models\User;
use App\Support\KanbanFilterRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;

/**
 * Coordinate board view filters, personal presentation and shared column capacity.
 */
class KanbanWorkspace
{
    /**
     * Read a stable storage key without changing legacy default-board preferences.
     *
     * @param string $type
     * @param ContentBoard|null $board
     *
     * @return string
     */
    public function key(string $type, ?ContentBoard $board): string
    {
        return $board ? $type.':board:'.$board->id : $type;
    }

    /**
     * Read private user presentation defaults.
     *
     * @param string $type
     * @param ContentBoard|null $board
     * @param User $actor
     *
     * @return array<string, mixed>
     */
    public function preferences(string $type, ?ContentBoard $board, User $actor): array
    {
        $newFields = ['assignee','scheduled','deadline','dependencies'];
        $defaults = ['density' => 'full','card_fields' => ['image','terms','author','updated','age',...$newFields],'group_by' => 'none'];
        $stored = $actor->ui_preferences['kanban'][$this->key($type, $board)] ?? [];
        $preferences = array_replace($defaults, $stored);
        if (!in_array($preferences['density'], ['full','compact'], true)) {
            $preferences['density'] = 'full';
        }
        $selected = is_array($preferences['card_fields']) ? $preferences['card_fields'] : $defaults['card_fields'];
        if (($stored['card_fields_version'] ?? 0) < 2) {
            $selected = array_merge($selected, $newFields);
        }
        $preferences['card_fields'] = array_values(array_intersect($defaults['card_fields'], $selected));
        $groups = ['none','author',...array_map(static fn ($key) => 'taxonomy:'.$key, array_keys(\JobMetric\Post\Facades\PostTypeRegistry::for($type)->getTaxonomyTypes()))];
        if (!in_array($preferences['group_by'], $groups, true)) {
            $preferences['group_by'] = 'none';
        }
        return $preferences;
    }

    /**
     * Read versioned, shared capacity options for either board mode.
     *
     * @param string $type
     * @param ContentBoard|null $board
     *
     * @return array<string, mixed>
     */
    public function options(string $type, ?ContentBoard $board): array
    {
        $row = Schema::hasTable('kanban_workspace_options') ? DB::table('kanban_workspace_options')->where('scope', $this->key($type, $board))->first() : null;
        $values = json_decode($row?->options ?? '{}', true) ?: [];
        return ['version' => (int) ($row?->version ?? 0), 'stale_days' => (int) ($values['stale_days'] ?? 7),
            'limits' => $values['limits'] ?? [], 'color_mode' => $values['color_mode'] ?? 'signals'];
    }

    /**
     * Add stable author and status-entry expressions to a board query.
     *
     * @param Builder<Post> $query
     *
     * @return Builder<Post>
     */
    public function metadata(Builder $query): Builder
    {
        $revisions = config('post.tables.content_revision', 'post_content_revisions');
        return $query->selectRaw('(SELECT ar.created_by FROM '.$revisions.' ar WHERE ar.stream_id = ks.id ORDER BY ar.id LIMIT 1) as board_author_id')->selectSub($this->enteredQuery(), 'state_entered_at_raw')
            ->selectRaw('ks.created_at as stream_created_at');
    }

    /**
     * Resolve the latest actual state change, excluding self-transitions.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function enteredQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table(config('post.tables.lifecycle_event', 'post_lifecycle_events'))->selectRaw('MAX(created_at)')->whereColumn('stream_id', 'ks.id')->whereIn('event', ['transition','workflow_migrated'])->whereColumn('context->from', '!=', 'context->to');
    }

    /**
     * Apply temporary, read-only filters after the board's fixed content restrictions.
     *
     * @param Builder<Post> $query
     * @param string $type
     * @param array<string, mixed> $data
     * @param int $staleDays
     *
     * @return Builder<Post>
     * @throws ValidationException
     */
    public function filter(Builder $query, string $type, array $data, int $staleDays): Builder
    {
        $table = (new Post())->getTable();
        if (!empty($data['search'])) {
            $search = trim($data['search']);
            $query->where(function ($q) use ($search, $table): void {
                if (ctype_digit($search)) {
                    $q->orWhere($table.'.id', (int)$search);
                }
                $q->orWhereHas('translations', fn ($translation) => $translation->where('locale', \App\Support\LocaleCatalog::resolve(app()->getLocale()))->where('field', 'title')->where('value', 'like', '%'.addcslashes($search, '%_\\').'%'));
            });
        }
        if (!empty($data['filters'])) {
            app(KanbanFilterRegistry::class)->apply($query, $type, $data['filters']);
        }
        if (($data['summary'] ?? '') === 'scheduled') {
            $query->whereExists(fn ($q) => $q->selectRaw('1')->from(config('post.tables.schedule', 'post_scheduled_actions').' as sched')->whereColumn('sched.stream_id', 'ks.id')->where('sched.state', 'pending'));
        }
        if (($data['summary'] ?? '') === 'stale') {
            $entered = $this->enteredQuery();
            $query->whereRaw('COALESCE(('.$entered->toSql().'),ks.created_at) <= ?', [...$entered->getBindings(),now()->subDays($staleDays)]);
        }
        return $query;
    }

    /**
     * Store capacity changes only after validation and while holding the workflow lock.
     *
     * @param string $type
     * @param ContentBoard|null $board
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function saveOptions(string $type, ?ContentBoard $board, array $data, User $actor): array
    {
        return DB::transaction(function () use ($type, $board, $data, $actor): array {
            app(\JobMetric\Post\Services\PostWorkflowDefinitions::class)->lock($type);
            if ($board) {
                $board = ContentBoard::whereKey($board->id)->lockForUpdate()->firstOrFail();
                abort_if($board->version !== (int)($data['board_version'] ?? 0), 409, __('kanban.stale'));
                app(ContentBoards::class)->accessible($type, $board->id, $actor);
            }
            $definition = app(PostLifecycleView::class)->activeDefinition($type);
            abort_unless($definition, 422);
            $states = array_column($definition->graph['states'], 'status');
            if ($board) {
                if (array_diff($board->states, $states) !== []) {
                    throw ValidationException::withMessages(['limits' => __('kanban.invalid_states')]);
                }
                $states = array_intersect($states, $board->states);
            }
            foreach ($data['limits'] as $limit) {
                if (!in_array($limit['state'], $states, true)) {
                    throw ValidationException::withMessages(['limits' => __('kanban.invalid_states')]);
                }
            }
            $current = $this->options($type, $board);
            abort_if($current['version'] !== (int)$data['options_version'], 409, __('kanban.stale'));
            $context = ['type' => $type,'id' => $board?->id,'user_id' => (int)$actor->id,'operation' => 'limits'];
            Hooks::lifecycle('jobic.posts.kanban.limits.saving', $context);
            $options = ['stale_days' => (int)$data['stale_days'],'limits' => $data['limits'],
                'color_mode' => $data['color_mode'] ?? $current['color_mode']];
            $values = ['type' => $type,'board_id' => $board?->id,'options' => json_encode($options, JSON_THROW_ON_ERROR),'version' => $current['version'] + 1,'updated_at' => now()];
            if ($current['version'] === 0) {
                $values['created_at'] = now();
            }
            DB::table('kanban_workspace_options')->updateOrInsert(['scope' => $this->key($type, $board)], $values);
            DB::afterCommit(static fn () => Hooks::kanbanOutcome('jobic.posts.kanban.limits.saved', $context));
            return $this->options($type, $board);
        });
    }

    /**
     * Enforce hard capacities for ordinary forms, scheduled execution and board moves alike.
     *
     * @param PostPublicationStream $stream
     *
     * @return void
     * @throws ValidationException
     */
    public static function checkCapacity(PostPublicationStream $stream): void
    {
        if ($stream->locale !== '*' || !$stream->isDirty('status') || !Schema::hasTable('kanban_workspace_options')) {
            return;
        }
        $post = $stream->post;
        if (!$post || !\JobMetric\Post\Facades\PostTypeRegistry::has($post->type) || !\JobMetric\Post\Facades\PostTypeRegistry::for($post->type)->hasKanban()) {
            return;
        }
        foreach (DB::table('kanban_workspace_options')->where('type', $post->type)->get() as $row) {
            $options = json_decode($row->options, true);
            $limit = collect($options['limits'] ?? [])->firstWhere('state', $stream->status);
            if (!$limit || ($limit['mode'] ?? 'warn') !== 'enforce' || (int)$limit['limit'] === 0) {
                continue;
            }
            $board = $row->board_id ? ContentBoard::find($row->board_id) : null;
            if ($row->board_id && (!$board || !\JobMetric\Post\Facades\PostTypeRegistry::for($post->type)->hasMultipleKanban())) {
                continue;
            }
            if ($board && !in_array($stream->status, $board->states, true)) {
                continue;
            }
            if ($board) {
                $match = Post::whereKey($post->id);
                app(KanbanFilterRegistry::class)->apply($match, $post->type, $board->filters);
                if (!$match->exists()) {
                    continue;
                }
            }
            $query = Post::where('type', $post->type)->where((new Post())->qualifyColumn('id'), '!=', $post->id)->whereHas('publicationStreams', fn ($q) => $q->where('locale', '*')->where('status', $stream->status));
            if ($board) {
                app(KanbanFilterRegistry::class)->apply($query, $post->type, $board->filters);
            }
            if ($query->count() >= (int)$limit['limit']) {
                throw ValidationException::withMessages(['state' => __('kanban.capacity_full')]);
            }
        }
    }
}
