<?php

declare(strict_types=1);
namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Http\Requests\Posts\KanbanCommandRequest;
use App\Http\Requests\Posts\KanbanReadRequest;
use App\Models\User;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\AdminAccess;
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
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostContentRevision;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Models\PostWorkflowDefinition;
use JobMetric\Post\Services\PostStreamWorkflow;
use JobMetric\Post\Services\WorkflowTransitionAccess;
use JobMetric\Post\Services\WorkflowTransitionPreview;
use Throwable;

/** Serve private, independently paginated workflow columns and atomic shared ordering. */
class Kanban
{
    /** Read page metadata without exposing inaccessible post data.
     *
     * @param string $type
     * @param User $actor
     *
     * @return array<string, mixed>
     */
    public function page(string $type, User $actor): array
    {
        return ['pageSize' => $this->pageSize($type, $actor), 'editable' => $this->mayMove($type, $actor),
            'copy' => trans('kanban'), 'dataUrl' => route('admin.posts.kanban.data', ['type' => $type]),
            'commandUrl' => route('admin.posts.kanban.command', ['type' => $type]),
            'detailUrl' => route('admin.posts.kanban.details', ['type' => $type, 'post' => '__ID__']),
            'backUrl' => route('admin.posts.index', ['type' => $type]),
            'typeLabel' => __(PostTypeRegistry::for($type)->getLabel()),
            'locales' => collect(LocaleCatalog::all())->map(fn ($language, $code) => ['code' => $code, 'name' => $language['name']])->values()->all()];
    }

    /** Read initial pages or one bounded state page using an encrypted versioned cursor.
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
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        if (! $definition) return Response::make(true, '', ['definition_id' => null, 'columns' => []]);
        abort_if(isset($data['definition_id']) && (int) $data['definition_id'] !== (int) $definition->id, 409, __('kanban.stale'));
        $columns = [];
        foreach ($definition->graph['states'] as $state) {
            if (isset($data['state']) && $data['state'] !== $state['status']) continue;
            $columns[] = $this->column($type, $state, $definition, $actor, $data['cursor'] ?? null);
        }
        abort_if(isset($data['state']) && $columns === [], 404);
        return Response::make(true, '', ['definition_id' => $definition->id, 'columns' => $columns]);
    }

    /** Read only the current actor's page size for one registered type.
     *
     * @param string $type
     * @param User $actor
     *
     * @return int
     */
    private function pageSize(string $type, User $actor): int
    {
        $size = (int) ($actor->ui_preferences['kanban'][$type]['page_size'] ?? 5);
        return in_array($size, [5, 10, 20, 50], true) ? $size : 5;
    }

    /** Resolve management through the shared Rolix parent and deny rules.
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

    /** Build the SQL ordering shared by pagination and movement validation.
     *
     * @param string $type
     * @param string $status
     *
     * @return Builder<Post>
     */
    private function query(string $type, string $status): Builder
    {
        $posts = (new Post)->getTable();
        $streams = (new PostPublicationStream)->getTable();
        return Post::query()->join($streams.' as ks', function ($join) use ($posts): void {
            $join->on('ks.post_id', '=', $posts.'.id')->where('ks.locale', PostPublicationStream::SHARED);
        })->leftJoin('post_kanban_columns as kc', function ($join) use ($type, $status): void {
            $join->where('kc.type', $type)->where('kc.status', $status);
        })->leftJoin('post_kanban_positions as kp', function ($join) use ($posts): void {
            $join->on('kp.post_id', '=', $posts.'.id')->on('kp.column_id', '=', 'kc.id');
        })->where($posts.'.type', $type)->where('ks.status', $status)
            ->select($posts.'.*', 'ks.id as publication_stream_id', 'ks.version as stream_version', 'ks.draft_revision_id', 'ks.status as workflow_status')
            ->selectRaw('COALESCE(kp.rank, '.$posts.'.id * 1024) as board_rank');
    }

    /** Read a column without leaking its card count when state roles deny visibility.
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
        $version = (int) DB::table('post_kanban_columns')->where('type', $type)->where('status', $state['status'])->value('version');
        $column = ['id' => $state['id'], 'status' => $state['status'], 'name' => $state['translations'][app()->getLocale()]['name'] ?? $state['name'],
            'restricted' => ! $visible, 'version' => $version, 'count' => null, 'items' => [], 'cursor' => null];
        if (! $visible) return $column;
        $query = $this->query($type, $state['status'])->where('ks.definition_id', $definition->id);
        $column['count'] = (clone $query)->count();
        if ($cursor) {
            try { $after = json_decode(Crypt::decryptString($cursor), true, flags: JSON_THROW_ON_ERROR); }
            catch (Throwable) { abort(422, __('kanban.invalid_cursor')); }
            abort_unless(($after['type'] ?? null) === $type && ($after['state'] ?? null) === $state['status']
                && ($after['actor'] ?? null) === (int) $actor->id, 422);
            abort_if((int) ($after['definition'] ?? 0) !== (int) $definition->id || (int) ($after['version'] ?? -1) !== $version, 409, __('kanban.stale'));
            $posts = (new Post)->getTable();
            $query->where(function ($q) use ($after, $posts): void {
                $rank = 'COALESCE(kp.rank, '.$posts.'.id * 1024)';
                $q->whereRaw($rank.' > ?', [$after['rank']])->orWhere(fn ($same) => $same->whereRaw($rank.' = ?', [$after['rank']])->where($posts.'.id', '>', $after['id']));
            });
        }
        $size = $this->pageSize($type, $actor);
        $rows = $query->with('translations')->orderBy('board_rank')->orderBy((new Post)->qualifyColumn('id'))->limit($size + 1)->get();
        $more = $rows->count() > $size;
        $page = $rows->take($size);
        $column['items'] = $page->map(fn (Post $post) => $this->card($post))->values()->all();
        if ($more && ($last = $page->last())) $column['cursor'] = Crypt::encryptString(json_encode([
            'type' => $type, 'state' => $state['status'], 'actor' => (int) $actor->id, 'definition' => (int) $definition->id,
            'version' => $version, 'rank' => (int) $last->board_rank, 'id' => (int) $last->id,
        ], JSON_THROW_ON_ERROR));
        return $column;
    }

    /** Present safe human-facing card fields from the shared draft.
     *
     * @param Post $post
     *
     * @return array<string, mixed>
     */
    private function card(Post $post): array
    {
        $author = PostContentRevision::where('stream_id', $post->publication_stream_id)->orderBy('id')->value('created_by');
        return ['id' => (int) $post->id, 'title' => $post->getTranslation('title', LocaleCatalog::resolve(app()->getLocale())) ?: __('kanban.untitled'),
            'author' => $author ? (User::find($author)?->name ?? __('kanban.unknown_author')) : __('kanban.unknown_author'),
            'updated_at' => LocalizedDate::format($post->updated_at), 'version' => (int) $post->stream_version,
            'revision_id' => (int) $post->draft_revision_id, 'state' => $post->workflow_status];
    }

    /** Read details and evaluate destinations without invoking actions.
     *
     * @param string $type
     * @param int $id
     * @param User $actor
     * @param string|null $locale
     *
     * @return Response
     * @throws Throwable
     */
    public function details(string $type, int $id, User $actor, ?string $locale = null): Response
    {
        $locale = LocaleCatalog::resolve($locale ?? app()->getLocale());
        $post = Post::where('type', $type)->with('translations')->findOrFail($id);
        abort_unless(app(PostStateAccess::class)->allows($post, $actor), 403);
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        $stream = PostPublicationStream::where('post_id', $id)->where('locale', '*')->firstOrFail();
        abort_unless($definition && (int) $stream->definition_id === (int) $definition->id, 409, __('kanban.stale'));
        $post->publication_stream_id = $stream->id; $post->stream_version = $stream->version; $post->draft_revision_id = $stream->draft_revision_id; $post->workflow_status = $stream->status;
        $states = collect($definition->graph['states'])->keyBy('id');
        $destinations = [];
        if ($this->mayMove($type, $actor)) foreach ($definition->graph['transitions'] as $edge) {
            if (($states[$edge['from']]['status'] ?? null) !== $stream->status) continue;
            $preview = app(WorkflowTransitionPreview::class)->inspect($edge, $stream, $actor);
            $target = $states[$edge['to']];
            $destinations[] = ['transition' => $edge['slug'], 'label' => $edge['label'], 'state' => $target['status'],
                'status' => $preview['status'], 'checks' => $preview['checks'], 'requires_due' => ($target['content_status'] ?? '') === 'future'];
        }
        $base = $type === 'page' ? 'admin.content.pages.manage' : 'admin.content.posts.manage';
        $mayEdit = app(AdminAccess::class)->allows($actor, PostTypeRegistry::for($type)->getManagePermission() ?? $base);
        return Response::make(true, '', ['card' => $this->card($post), 'definition_id' => $definition->id,
            'locale' => $locale, 'excerpt' => $post->getTranslation('excerpt', $locale),
            'content_html' => app(\App\Support\EditorRenderer::class)->render(app(\App\Services\Editor\EditorDocuments::class)->readForEditor($post, $locale)), 'destinations' => $destinations,
            'edit_url' => $mayEdit ? route('admin.posts.edit', ['type' => $type, 'post' => $id, 'embedded' => 'kanban']) : null,
            'history_url' => $mayEdit ? route('admin.posts.history', ['type' => $type, 'post' => $id]) : null]);
    }

    /** Persist preferences or perform one version-checked atomic movement.
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
        if (! empty($data['due_local'])) $data['due_at'] = \App\Support\CalendarInput::parse($data['due_local']);
        $data = dto($data, KanbanCommandRequest::class);
        if ($data['operation'] === 'settings') {
            DB::transaction(function () use ($type, $data, $actor): void {
                $user = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
                $context = ['type' => $type, 'user_id' => (int) $actor->id];
                Hooks::lifecycle('jobic.posts.kanban.settings.saving', $context);
                $preferences = $user->ui_preferences ?? [];
                $preferences['kanban'][$type]['page_size'] = (int) $data['page_size'];
                $user->ui_preferences = $preferences; $user->save();
                DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.settings.saved', $context));
            });
            return Response::make(true, __('kanban.saved'), ['page_size' => (int) $data['page_size']]);
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
                app(\JobMetric\Post\Services\PostWorkflowDefinitions::class)->lock($type);
                $post = Post::where('type', $type)->whereKey($id)->lockForUpdate()->firstOrFail();
                abort_unless(app(PostStateAccess::class)->allows($post, $actor), 403);
                $definition = app(PostLifecycleView::class)->activeDefinition($type);
                abort_unless($definition && (int) $definition->id === (int) $data['definition_id'], 409, __('kanban.stale'));
                $stream = PostPublicationStream::where('post_id', $id)->where('locale', '*')->lockForUpdate()->firstOrFail();
                abort_if((int) $stream->version !== (int) $data['version'] || (int) $stream->draft_revision_id !== (int) $data['revision_id'], 409, __('kanban.stale'));
                $states = collect($definition->graph['states'])->keyBy('status');
                abort_unless($states->has($data['state']), 422);
                // Serialize all movements of a type, including cross-column moves, on its owning workflow row.
                DB::table(config('post.tables.workflow', 'post_workflows'))->where('type', $type)->lockForUpdate()->first();
                foreach (array_unique([$stream->status, $data['state']]) as $status) {
                    DB::table('post_kanban_columns')->insertOrIgnore(['type' => $type, 'status' => $status, 'version' => 0]);
                }
                $target = DB::table('post_kanban_columns')->where('type', $type)->where('status', $data['state'])->lockForUpdate()->first();
                abort_if((int) $target->version !== (int) $data['column_version'], 409, __('kanban.stale'));
                if (isset($data['before_id'])) {
                    $targetRoles = $states[$data['state']]['view_roles'] ?? [];
                    abort_if($targetRoles !== [] && ! app(PostStateAccess::class)->hasAnySystemRole($actor, $targetRoles), 403);
                    abort_unless($this->query($type, $data['state'])->where((new Post)->qualifyColumn('id'), $data['before_id'])->exists(), 409, __('kanban.stale'));
                }
                if ($data['operation'] === 'move') {
                    $candidate = collect($definition->graph['transitions'])->firstWhere('slug', $data['transition']);
                    abort_unless($candidate && ($states[$data['state']]['id'] ?? null) === $candidate['to']
                        && ($states[$stream->status]['id'] ?? null) === $candidate['from'], 422);
                    abort_unless(app(WorkflowTransitionAccess::class)->allows($candidate, $actor), 403, trans('post::lifecycle.role_denied'));
                }
                $context = ['type' => $type, 'post_id' => $id, 'user_id' => (int) $actor->id, 'operation' => $data['operation']];
                Hooks::lifecycle('jobic.posts.kanban.moving', $context);
                if ($data['operation'] === 'move') {
                    $source = DB::table('post_kanban_columns')->where('type', $type)->where('status', $stream->status)->first();
                    abort_if((int) $source->version !== (int) $data['source_column_version'], 409, __('kanban.stale'));
                    $edge = collect($definition->graph['transitions'])->firstWhere('slug', $data['transition']);
                    abort_unless($edge && ($states[$data['state']]['id'] ?? null) === $edge['to'], 422);
                    app(PostStreamWorkflow::class)->transition($post, '*', $data['transition'], (int) $data['revision_id'], (int) $data['version'], $actor,
                        empty($data['due_at']) ? null : Carbon::parse($data['due_at']));
                } else {
                    abort_unless($stream->status === $data['state'], 422);
                }
                $rank = $this->rankBefore($type, $data['state'], (int) $target->id, $id, isset($data['before_id']) ? (int) $data['before_id'] : null);
                DB::table('post_kanban_positions')->updateOrInsert(['post_id' => $id], ['column_id' => $target->id, 'rank' => $rank]);
                DB::table('post_kanban_columns')->where('id', $target->id)->increment('version');
                DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.moved', $context));
            });
        } catch (\JobMetric\Post\Exceptions\PostStatusTransitionNotAllowedException $error) {
            return Response::make(false, __('kanban.stale'), null, 409);
        } catch (TaskRestrictionException $error) {
            $status = $error->getMessage() === trans('post::lifecycle.role_denied') || $error->getMessage() === trans('post::lifecycle.permission_denied') ? 403 : 422;
            return Response::make(false, $error->getMessage(), null, $status);
        } finally {
            if (! $existing) $locks->release('posts:'.$type, $id, (int) $actor->id, $lease['lock']['token']);
        }
        return Response::make(true, __('kanban.moved'), ['id' => $id]);
    }

    /** Allocate a rank by querying immediate neighbors; rebalance only when a gap is exhausted.
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
        $table = (new Post)->getTable();
        $base = $this->query($type, $status)->where($table.'.id', '!=', $postId);
        $before = $beforeId ? (clone $base)->where($table.'.id', $beforeId)->first() : null;
        abort_if($beforeId && ! $before, 409, __('kanban.stale'));
        $rankSql = 'COALESCE(kp.rank, '.$table.'.id * 1024)';
        $previousQuery = clone $base;
        if ($before) $previousQuery->where(function ($query) use ($before, $rankSql, $table): void {
            $query->whereRaw($rankSql.' < ?', [$before->board_rank])->orWhere(fn ($q) => $q->whereRaw($rankSql.' = ?', [$before->board_rank])->where($table.'.id', '<', $before->id));
        });
        $previous = (int) ($previousQuery->orderByDesc('board_rank')->orderByDesc($table.'.id')->first()?->board_rank ?? 0);
        $next = $before ? (int) $before->board_rank : $previous + 2048;
        if ($next - $previous >= 2) return $previous + intdiv($next - $previous, 2);
        $index = 0; $beforeIndex = null;
        foreach ((clone $base)->orderBy('board_rank')->orderBy($table.'.id')->cursor() as $row) {
            if ($beforeId === (int) $row->id) $beforeIndex = $index;
            $index++;
            DB::table('post_kanban_positions')->updateOrInsert(['post_id' => $row->id], ['column_id' => $columnId, 'rank' => $index * 1024]);
        }
        return (($beforeIndex ?? $index) * 1024) + 512;
    }

    /** Invalidate column cursors and ranks for transitions made through any entry point.
     *
     * @param PostPublicationStream $stream
     *
     * @return void
     */
    public static function streamChanged(PostPublicationStream $stream): void
    {
        if ($stream->locale !== '*' || ! $stream->wasChanged('status') || ! Schema::hasTable('post_kanban_columns') || ! Schema::hasTable('post_kanban_positions')) return;
        $post = $stream->post;
        if (! $post || ! PostTypeRegistry::for($post->type)->hasKanban()) return;
        $statuses = array_unique([$stream->getRawOriginal('status'), $stream->status]);
        foreach ($statuses as $status) DB::table('post_kanban_columns')->insertOrIgnore(['type' => $post->type, 'status' => $status, 'version' => 0]);
        $column = DB::table('post_kanban_columns')->where('type', $post->type)->where('status', $stream->status)->first();
        $last = (new self)->query($post->type, $stream->status)->where((new Post)->qualifyColumn('id'), '!=', $post->id)
            ->orderByDesc('board_rank')->orderByDesc((new Post)->qualifyColumn('id'))->first();
        DB::table('post_kanban_positions')->updateOrInsert(['post_id' => $post->id], ['column_id' => $column->id, 'rank' => (int) ($last?->board_rank ?? 0) + 1024]);
        DB::table('post_kanban_columns')->where('type', $post->type)->whereIn('status', $statuses)->increment('version');
    }
}
