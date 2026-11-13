<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Models\User;
use App\Support\LocalizedDate;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Facades\PostTypeRegistry;

/**
 * Keep private editorial comments scoped to the visible board card.
 */
final class KanbanDiscussion
{
    /**
     * Read the newest bounded discussion messages for one authorized card.
     *
     * @param int $postId
     * @param int|null $beforeId
     *
     * @return array<string, mixed>
     */
    public function read(int $postId, ?int $beforeId = null): array
    {
        $query = DB::table('kanban_card_comments')->where('post_id', $postId);
        if ($beforeId !== null) $query->where('id', '<', $beforeId);
        $rows = $query->orderByDesc('id')->limit(21)->get();
        $more = $rows->count() > 20;
        $rows = $rows->take(20);
        $authors = User::query()->whereIn('id', $rows->pluck('user_id')->all())->get()->keyBy('id');
        $items = $rows->map(static fn ($row): array => ['id' => (int) $row->id,
            'body' => $row->body, 'author' => $authors->get($row->user_id)?->name ?? __('kanban.unknown_user'),
            'avatar_url' => $authors->get($row->user_id)?->avatarUrl(),
            'created_at' => LocalizedDate::format($row->created_at)])->values()->all();

        return ['items' => $items, 'cursor' => $more ? (int) $rows->last()->id : null];
    }

    /**
     * Add an editorial comment and notify only mentioned users who can view this card.
     *
     * @param string $type
     * @param Kanban $board
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws ValidationException
     */
    public function add(string $type, Kanban $board, array $data, User $actor): Response
    {
        $postId = (int) $data['post_id'];
        $board->visiblePost($type, $postId, $actor);
        $body = trim((string) $data['comment_body']);
        preg_match_all('/@\[[^\]]{1,80}\]\(user:(\d+)\)/u', $body, $matches);
        $mentions = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        if (count($mentions) > 5) throw ValidationException::withMessages(['comment_body' => __('kanban.too_many_mentions')]);
        $recipients = User::query()->where('status', true)->whereIn('id', $mentions)->get();
        if ($recipients->count() !== count($mentions)) throw ValidationException::withMessages(['comment_body' => __('kanban.invalid_mention')]);
        foreach ($recipients as $recipient) {
            $fallback = $type === 'page' ? 'admin.content.pages.view' : 'admin.content.posts.view';
            abort_unless(app(AdminAccess::class)->allows($recipient, PostTypeRegistry::for($type)->getViewPermission() ?? $fallback), 422);
            $board->visiblePost($type, $postId, $recipient);
            if ($board->selectedBoard()) app(ContentBoards::class)->accessible($type, (int) $board->selectedBoard()->id, $recipient);
        }
        $id = DB::transaction(function () use ($type, $postId, $actor, $body, $mentions): int {
            $context = ['type' => $type, 'post_id' => $postId, 'user_id' => (int) $actor->id];
            Hooks::action('jobic.posts.kanban.comment.saving', $context);
            $id = (int) DB::table('kanban_card_comments')->insertGetId(['post_id' => $postId, 'user_id' => $actor->id,
                'body' => $body, 'created_at' => now(), 'updated_at' => now()]);
            foreach ($mentions as $recipientId) app(KanbanNotifications::class)->send($recipientId, $postId, 'mention', (int) $actor->id);
            DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.comment.saved', $context + ['comment_id' => $id]));
            return $id;
        });
        app(\App\Support\KanbanRealtime::class)->changed($type);

        return Response::make(true, __('kanban.comment_saved'), ['comment_id' => $id, 'discussion' => $this->read($postId)]);
    }
}
