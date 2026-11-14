<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Models\KanbanCardMeta;
use App\Models\User;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\AdminAccess;
use App\Support\LocaleCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use Throwable;

/**
 * Manage board assignment, editorial deadlines and card dependencies.
 */
final class KanbanCardWork
{
    /**
     * Read editorial metadata for one already authorized board card.
     *
     * @param int $postId
     * @param Kanban $board
     * @param User $actor
     *
     * @return array<string, mixed>
     */
    public function read(int $postId, Kanban $board, User $actor): array
    {
        $meta = KanbanCardMeta::query()->find($postId);
        $priority = DB::table('kanban_card_priorities')->where('post_id', $postId)->value('priority') ?? 'normal';
        $assignee = $meta?->assignee_id ? User::query()->find($meta->assignee_id) : null;
        $dependencies = DB::table('kanban_card_dependencies as link')
            ->join((new Post())->getTable().' as dependency', 'dependency.id', '=', 'link.depends_on_post_id')
            ->where('link.post_id', $postId)->orderBy('dependency.id')
            ->get(['dependency.id', 'dependency.type'])
            ->filter(function ($row) use ($board, $actor): bool {
                try {
                    $board->visiblePost((string) $row->type, (int) $row->id, $actor);
                    return true;
                } catch (Throwable) {
                    return false;
                }
            })
            ->map(static fn ($row): array => ['id' => (int) $row->id, 'type' => $row->type])->values()->all();
        if ($dependencies !== []) {
            $titles = Post::query()->whereIn('id', array_column($dependencies, 'id'))->with('translations')->get()
                ->mapWithKeys(static fn (Post $post): array => [(int) $post->id => $post->getTranslation('title', LocaleCatalog::resolve(app()->getLocale()))])->all();
            $dependencies = array_map(static fn (array $item): array => $item + ['title' => $titles[$item['id']] ?? '#'.$item['id']], $dependencies);
        }

        return [
            'assignee' => $assignee ? ['id' => (int) $assignee->id, 'name' => $assignee->name, 'avatar_url' => $assignee->avatarUrl()] : null,
            'due_at' => $meta?->due_local,
            'due_raw' => $meta?->getRawOriginal('due_at'),
            'due_state' => $meta?->due_at ? (now()->greaterThan($meta->due_at) ? 'overdue' : (now()->addDay()->greaterThanOrEqualTo($meta->due_at) ? 'soon' : null)) : null,
            'version' => (int) ($meta?->version ?? 0),
            'priority' => $priority,
            'dependencies' => $dependencies,
        ];
    }

    /**
     * Save card work fields and dependency links after board access and version checks.
     *
     * @param string $type
     * @param Kanban $board
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws ValidationException
     */
    public function save(string $type, Kanban $board, array $data, User $actor): Response
    {
        $post = $board->visiblePost($type, (int) $data['post_id'], $actor);
        $assigneeId = $data['assignee_id'] ?? null;
        if ($assigneeId) {
            $assignee = User::query()->where('status', true)->findOrFail((int) $assigneeId);
            $viewPermission = PostTypeRegistry::for($type)->getViewPermission() ?? ($type === 'page' ? 'admin.content.pages.view' : 'admin.content.posts.view');
            abort_unless(app(AdminAccess::class)->allows($assignee, $viewPermission), 422);
            abort_unless(app(PostStateAccess::class)->allows($post, $assignee), 422);
            if ($board->selectedBoard()) {
                app(ContentBoards::class)->accessible($type, (int) $board->selectedBoard()->id, $assignee);
            }
        }
        $dependencies = array_values(array_unique(array_map('intval', $data['dependency_ids'] ?? [])));
        foreach ($dependencies as $id) {
            if ($id === (int) $post->id) {
                throw ValidationException::withMessages(['dependency_ids' => __('kanban.dependency_self')]);
            }
            $board->visiblePost($type, $id, $actor);
        }
        $locks = app(TaxonomyEditLocks::class);
        $existing = $locks->inspect('posts:'.$type, (int) $post->id);
        $lease = $locks->acquire('posts:'.$type, (int) $post->id, $actor);
        abort_unless($lease['acquired'], 423, __('kanban.locked'));
        try {
            DB::transaction(function () use ($type, $board, $post, $data, $actor, $assigneeId, $dependencies): void {
                app(\JobMetric\Post\Services\PostWorkflowDefinitions::class)->lock($type);
                if ($board->selectedBoard()) {
                    $freshBoard = \App\Models\ContentBoard::whereKey($board->selectedBoard()->id)->lockForUpdate()->firstOrFail();
                    abort_if((int) $freshBoard->version !== (int) ($data['board_version'] ?? 0), 409, __('kanban.stale'));
                    app(ContentBoards::class)->accessible($type, (int) $freshBoard->id, $actor);
                }
                $board->visiblePost($type, (int) $post->id, $actor);
                foreach ($dependencies as $id) {
                    $board->visiblePost($type, $id, $actor);
                }
                $this->checkCycles((int) $post->id, $dependencies);
                $current = KanbanCardMeta::query()->whereKey($post->id)->lockForUpdate()->first();
                abort_if((int) ($current?->version ?? 0) !== (int) $data['meta_version'], 409, __('kanban.stale'));
                $board->checkVersion($data);
                $context = ['type' => $type, 'post_id' => (int) $post->id, 'user_id' => (int) $actor->id, 'operation' => 'card_work'];
                Hooks::lifecycle('jobic.posts.kanban.card_work.saving', $context);
                $meta = $current ?? new KanbanCardMeta(['post_id' => (int) $post->id]);
                $previousAssignee = (int) ($current?->assignee_id ?? 0);
                $meta->assignee_id = $assigneeId ?: null;
                $meta->due_local = $data['work_due_local'] ?? null;
                $meta->version = (int) ($current?->version ?? 0) + 1;
                $meta->save();
                if (isset($data['priority'])) {
                    DB::table('kanban_card_priorities')->updateOrInsert(['post_id' => (int) $post->id],
                        ['priority' => $data['priority'], 'updated_at' => now(), 'created_at' => now()]);
                }
                if ($assigneeId && (int) $assigneeId !== $previousAssignee) {
                    app(KanbanNotifications::class)->send((int) $assigneeId, (int) $post->id, 'assignment', (int) $actor->id);
                }
                DB::table('kanban_card_dependencies')->where('post_id', $post->id)->delete();
                if ($dependencies !== []) {
                    DB::table('kanban_card_dependencies')->insert(array_map(static fn (int $id): array => [
                        'post_id' => (int) $post->id, 'depends_on_post_id' => $id, 'created_at' => now(), 'updated_at' => now(),
                    ], $dependencies));
                }
                DB::afterCommit(static fn () => Hooks::kanbanOutcome('jobic.posts.kanban.card_work.saved', $context));
            });
        } finally {
            if (! $existing) {
                $locks->release('posts:'.$type, (int) $post->id, (int) $actor->id, $lease['lock']['token']);
            }
        }
        app(\App\Support\KanbanRealtime::class)->changed($type);

        return Response::make(true, __('kanban.saved'), ['work' => $this->read((int) $post->id, $board, $actor)]);
    }

    /**
     * Reject dependency chains that would loop back to the edited card.
     *
     * @param int $postId
     * @param array<int, int> $dependencies
     *
     * @return void
     * @throws ValidationException
     */
    private function checkCycles(int $postId, array $dependencies): void
    {
        $frontier = $dependencies;
        $seen = [];
        while ($frontier !== []) {
            if (in_array($postId, $frontier, true)) {
                throw ValidationException::withMessages(['dependency_ids' => __('kanban.dependency_cycle')]);
            }
            $new = array_values(array_diff($frontier, $seen));
            if ($new === []) break;
            $seen = array_merge($seen, $new);
            $frontier = DB::table('kanban_card_dependencies')->whereIn('post_id', $new)->pluck('depends_on_post_id')->map(static fn ($id): int => (int) $id)->all();
        }
    }

    /**
     * Report whether every linked prerequisite is already published.
     *
     * @param int $postId
     * @param string $type
     *
     * @return bool
     */
    public static function dependenciesReady(int $postId, string $type): bool
    {
        $ids = DB::table('kanban_card_dependencies')->where('post_id', $postId)->pluck('depends_on_post_id')->all();
        if ($ids === []) return true;

        return Post::query()->where('type', $type)->whereIn('id', $ids)->where('status', 'publish')->count() === count($ids);
    }

    /**
     * Stop direct or scheduled shared publication while a prerequisite is unfinished.
     *
     * @param PostPublicationStream $stream
     *
     * @return void
     * @throws ValidationException
     */
    public static function blockUnreadyPublication(PostPublicationStream $stream): void
    {
        if ($stream->locale !== PostPublicationStream::SHARED || $stream->content_status !== 'publish'
            || ! ($stream->isDirty('content_status') || $stream->isDirty('live_revision_id') || $stream->isDirty('published_at'))) {
            return;
        }
        $type = $stream->post?->type;
        if ($type && ! self::dependenciesReady((int) $stream->post_id, (string) $type)) {
            throw ValidationException::withMessages(['dependency_ids' => __('kanban.dependency_blocked')]);
        }
    }
}
