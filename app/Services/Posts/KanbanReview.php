<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Models\User;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\LocalizedDate;
use App\Support\AdminAccess;
use App\Support\PostWorkflowPermissions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Services\PostWorkflowDefinitions;

/**
 * Coordinate several editorial reviewers before the package approves a revision.
 */
final class KanbanReview
{
    /**
     * Describe the current revision's review without exposing unrelated revisions.
     *
     * @param Post $post
     * @param PostPublicationStream $stream
     * @param bool $enabled
     * @param User $actor
     *
     * @return array<string, mixed>
     */
    public function read(Post $post, PostPublicationStream $stream, bool $enabled, User $actor): array
    {
        $rows = DB::table('kanban_card_reviews')->where('post_id', $post->id)->where('revision_id', $stream->draft_revision_id)
            ->orderBy('id')->get();
        $users = User::query()->whereIn('id', $rows->pluck('reviewer_id')->all())->get()->keyBy('id');
        $reviewers = $rows->map(static fn ($row): array => ['id' => (int) $row->reviewer_id,
            'name' => $users->get($row->reviewer_id)?->name ?? __('kanban.unknown_user'), 'status' => $row->status,
            'decided_at' => LocalizedDate::format($row->decided_at)])->all();
        $approved = (int) $stream->approved_revision_id === (int) $stream->draft_revision_id && (int) $stream->draft_revision_id > 0;
        $rejected = $rows->contains('status', 'rejected');
        $mine = $rows->firstWhere('reviewer_id', $actor->id);

        return ['enabled' => $enabled, 'status' => $approved ? 'approved' : ($rejected ? 'rejected' : ($rows->isEmpty() ? 'available' : 'pending')),
            'reviewers' => $reviewers, 'can_decide' => $enabled && ! $approved && ! $rejected && $mine?->status === 'pending',
            'can_request' => $enabled && ! $approved, 'revision_id' => (int) $stream->draft_revision_id];
    }

    /**
     * Submit the current revision to one through five authorized board reviewers.
     *
     * @param string $type
     * @param Kanban $board
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws ValidationException
     */
    public function request(string $type, Kanban $board, array $data, User $actor): Response
    {
        $post = $board->visiblePost($type, (int) $data['post_id'], $actor);
        $ids = array_values(array_unique(array_map('intval', $data['reviewer_ids'])));
        foreach ($ids as $id) {
            $reviewer = User::query()->where('status', true)->find($id);
            if (! $reviewer || $id === (int) $actor->id) throw ValidationException::withMessages(['reviewer_ids' => __('kanban.invalid_reviewer')]);
            $fallback = $type === 'page' ? 'admin.content.pages.view' : 'admin.content.posts.view';
            abort_unless(app(AdminAccess::class)->allows($reviewer, PostTypeRegistry::for($type)->getViewPermission() ?? $fallback), 422);
            abort_unless(app(AdminAccess::class)->allows($reviewer, app(PostWorkflowPermissions::class)->for($type)['manage']), 422);
            $board->visiblePost($type, (int) $post->id, $reviewer);
            if ($board->selectedBoard()) app(ContentBoards::class)->accessible($type, (int) $board->selectedBoard()->id, $reviewer);
        }
        $this->withLease($type, (int) $post->id, $actor, function () use ($type, $post, $data, $actor, $ids): void {
            DB::transaction(function () use ($type, $post, $data, $actor, $ids): void {
            app(PostWorkflowDefinitions::class)->lock($type);
            Post::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $stream = PostPublicationStream::query()->where('post_id', $post->id)->where('locale', '*')->lockForUpdate()->firstOrFail();
            abort_if((int) $stream->version !== (int) $data['version'] || (int) $stream->draft_revision_id !== (int) $data['revision_id'], 409, __('kanban.stale'));
            $this->ensureEnabled($type, $stream);
            abort_if((int) $stream->approved_revision_id === (int) $stream->draft_revision_id, 409, __('kanban.stale'));
            abort_if(DB::table('kanban_card_reviews')->where('post_id', $post->id)
                ->where('revision_id', $stream->draft_revision_id)->where('status', 'pending')->exists(), 409, __('kanban.stale'));
            $context = ['type' => $type, 'post_id' => (int) $post->id, 'user_id' => (int) $actor->id,
                'revision_id' => (int) $stream->draft_revision_id, 'operation' => 'request'];
            Hooks::lifecycle('jobic.posts.kanban.review.saving', $context);
            if ((int) $stream->submitted_revision_id !== (int) $stream->draft_revision_id) {
                app(PostLifecycle::class)->submit($post, '*', (int) $stream->draft_revision_id, $actor, (int) $stream->version);
            }
            DB::table('kanban_card_reviews')->where('post_id', $post->id)->where('revision_id', $stream->draft_revision_id)->delete();
            foreach ($ids as $id) {
                DB::table('kanban_card_reviews')->insert(['post_id' => $post->id, 'revision_id' => $stream->draft_revision_id,
                    'reviewer_id' => $id, 'requested_by' => $actor->id, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
                app(KanbanNotifications::class)->send($id, (int) $post->id, 'review_requested', (int) $actor->id);
            }
            DB::afterCommit(static fn () => Hooks::kanbanOutcome('jobic.posts.kanban.review.saved', $context));
            });
        });
        app(\App\Support\KanbanRealtime::class)->changed($type);

        return Response::make(true, __('kanban.review_requested'));
    }

    /**
     * Record a review vote and approve the immutable revision after every reviewer agrees.
     *
     * @param string $type
     * @param Kanban $board
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     */
    public function decide(string $type, Kanban $board, array $data, User $actor): Response
    {
        $post = $board->visiblePost($type, (int) $data['post_id'], $actor);
        $this->withLease($type, (int) $post->id, $actor, function () use ($type, $post, $data, $actor): void {
            DB::transaction(function () use ($type, $post, $data, $actor): void {
            app(PostWorkflowDefinitions::class)->lock($type);
            Post::query()->whereKey($post->id)->lockForUpdate()->firstOrFail();
            $stream = PostPublicationStream::query()->where('post_id', $post->id)->where('locale', '*')->lockForUpdate()->firstOrFail();
            abort_if((int) $stream->version !== (int) $data['version'] || (int) $stream->draft_revision_id !== (int) $data['revision_id'], 409, __('kanban.stale'));
            $this->ensureEnabled($type, $stream);
            $review = DB::table('kanban_card_reviews')->where('post_id', $post->id)->where('revision_id', $stream->draft_revision_id)
                ->where('reviewer_id', $actor->id)->lockForUpdate()->first();
            abort_unless($review && $review->status === 'pending', 403);
            abort_if(DB::table('kanban_card_reviews')->where('post_id', $post->id)->where('revision_id', $stream->draft_revision_id)->where('status', 'rejected')->exists(), 409, __('kanban.stale'));
            $context = ['type' => $type, 'post_id' => (int) $post->id, 'user_id' => (int) $actor->id,
                'revision_id' => (int) $stream->draft_revision_id, 'operation' => 'decide'];
            Hooks::lifecycle('jobic.posts.kanban.review.saving', $context);
            $status = $data['decision'] === 'approve' ? 'approved' : 'rejected';
            DB::table('kanban_card_reviews')->where('id', $review->id)->update(['status' => $status, 'decided_at' => now(), 'updated_at' => now()]);
            if ($status === 'rejected') {
                app(PostLifecycle::class)->reject($post, '*', (int) $stream->draft_revision_id, $actor, (int) $stream->version);
            } elseif (! DB::table('kanban_card_reviews')->where('post_id', $post->id)->where('revision_id', $stream->draft_revision_id)->where('status', 'pending')->exists()) {
                app(PostLifecycle::class)->approve($post, '*', (int) $stream->draft_revision_id, $actor, (int) $stream->version);
            }
            if ($review->requested_by) app(KanbanNotifications::class)->send((int) $review->requested_by, (int) $post->id, 'review_decided', (int) $actor->id);
            DB::afterCommit(static fn () => Hooks::kanbanOutcome('jobic.posts.kanban.review.saved', $context));
            });
        });
        app(\App\Support\KanbanRealtime::class)->changed($type);

        return Response::make(true, __('kanban.review_saved'));
    }

    /**
     * Reject review commands outside an approval-enabled workflow state.
     *
     * @param string $type
     * @param PostPublicationStream $stream
     *
     * @return void
     * @throws ValidationException
     */
    private function ensureEnabled(string $type, PostPublicationStream $stream): void
    {
        $definition = app(PostLifecycleView::class)->activeDefinition($type);
        $state = collect($definition?->graph['states'] ?? [])->firstWhere('status', $stream->status);
        if (! ($state['allows_approval'] ?? false)) throw ValidationException::withMessages(['operation' => __('kanban.review_unavailable')]);
    }

    /**
     * Preserve another editor's lease while releasing only a lease acquired by this command.
     *
     * @param string $type
     * @param int $postId
     * @param User $actor
     * @param callable $action
     *
     * @return void
     */
    private function withLease(string $type, int $postId, User $actor, callable $action): void
    {
        $locks = app(TaxonomyEditLocks::class);
        $scope = 'posts:'.$type;
        $existing = $locks->inspect($scope, $postId);
        $lease = $locks->acquire($scope, $postId, $actor);
        abort_unless($lease['acquired'], 423, __('kanban.locked'));
        try {
            $action();
        } finally {
            if (! $existing) {
                $locks->release($scope, $postId, (int) $actor->id, $lease['lock']['token']);
            }
        }
    }
}
