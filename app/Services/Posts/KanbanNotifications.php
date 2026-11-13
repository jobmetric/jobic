<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Models\User;
use App\Support\LocalizedDate;
use App\Support\KanbanExtensions;
use App\Support\AdminAccess;
use Illuminate\Support\Facades\DB;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use Throwable;

/**
 * Deliver private board activity to authorized recipients without storing card text.
 */
final class KanbanNotifications
{
    /**
     * Store one in-panel notification after the associated action is validated.
     *
     * @param int $recipientId
     * @param int $postId
     * @param string $kind
     * @param int|null $actorId
     * @param string|null $dedupeKey
     *
     * @return bool
     */
    public function send(int $recipientId, int $postId, string $kind, ?int $actorId = null, ?string $dedupeKey = null): bool
    {
        if ($recipientId === $actorId || ! in_array($kind, ['mention', 'assignment', 'deadline', 'review_requested', 'review_decided'], true)) {
            return false;
        }
        $recipient = User::query()->where('status', true)->find($recipientId);
        if (! $recipient || ! ($recipient->ui_preferences['kanban_notifications'][$kind] ?? true)) {
            return false;
        }
        $context = ['user_id' => $recipientId, 'post_id' => $postId, 'operation' => $kind];
        Hooks::action('jobic.posts.kanban.notification.sending', $context);
        $inserted = DB::table('kanban_notifications')->insertOrIgnore(['user_id' => $recipientId, 'post_id' => $postId,
            'actor_id' => $actorId, 'kind' => $kind, 'dedupe_key' => $dedupeKey, 'created_at' => now()]) > 0;
        if ($inserted) DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.notification.sent', $context));
        return $inserted;
    }

    /**
     * List only notifications whose cards remain visible to their recipient.
     *
     * @param string $type
     * @param Kanban $board
     * @param User $actor
     *
     * @return Response
     */
    public function feed(string $type, Kanban $board, User $actor): Response
    {
        $items = [];
        $unread = 0;
        foreach (DB::table('kanban_notifications')->where('user_id', $actor->id)->orderByDesc('id')->limit(100)->get() as $row) {
            try {
                $board->visiblePost($type, (int) $row->post_id, $actor);
            } catch (Throwable) {
                continue;
            }
            if ($row->read_at === null) $unread++;
            if (count($items) < 20) {
                $items[] = ['id' => (int) $row->id, 'post_id' => (int) $row->post_id, 'kind' => $row->kind,
                    'read' => $row->read_at !== null, 'created_at' => LocalizedDate::format($row->created_at)];
            }
        }

        return Response::make(true, '', ['items' => $items, 'unread' => $unread,
            'preferences' => $this->preferences($actor), 'extra' => KanbanExtensions::data('notifications', ['type' => $type, 'id' => $board->selectedBoard()?->id, 'user_id' => (int) $actor->id])]);
    }

    /**
     * Mark one owned and currently visible notification as read.
     *
     * @param string $type
     * @param Kanban $board
     * @param User $actor
     * @param int $id
     *
     * @return Response
     */
    public function markRead(string $type, Kanban $board, User $actor, int $id): Response
    {
        $row = DB::table('kanban_notifications')->where('user_id', $actor->id)->where('id', $id)->first();
        abort_unless($row, 404);
        $board->visiblePost($type, (int) $row->post_id, $actor);
        $context = ['type' => $type, 'post_id' => (int) $row->post_id, 'user_id' => (int) $actor->id];
        Hooks::action('jobic.posts.kanban.notification.reading', $context);
        $updated = DB::table('kanban_notifications')->where('id', $id)->whereNull('read_at')->update(['read_at' => now()]);
        if ($updated) DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.notification.read', $context));

        return Response::make(true, __('kanban.saved'));
    }

    /**
     * Return the user's opt-in switches with all supported kinds enabled by default.
     *
     * @param User $actor
     *
     * @return array<string, bool>
     */
    public function preferences(User $actor): array
    {
        $stored = $actor->ui_preferences['kanban_notifications'] ?? [];
        $preferences = [];
        foreach (['mention', 'assignment', 'deadline', 'review_requested', 'review_decided'] as $key) {
            $preferences[$key] = (bool) ($stored[$key] ?? true);
        }
        return $preferences;
    }

    /**
     * Save private notification switches without storing board data in hook contexts.
     *
     * @param User $actor
     * @param array<string, bool> $values
     *
     * @return Response
     */
    public function savePreferences(User $actor, array $values): Response
    {
        DB::transaction(function () use ($actor, $values): void {
            $user = User::query()->whereKey($actor->id)->lockForUpdate()->firstOrFail();
            $context = ['user_id' => (int) $actor->id];
            Hooks::action('jobic.posts.kanban.notifications.saving', $context);
            $preferences = $user->ui_preferences ?? [];
            foreach (array_keys($this->preferences($user)) as $kind) {
                $preferences['kanban_notifications'][$kind] = (bool) ($values[$kind] ?? true);
            }
            $user->ui_preferences = $preferences;
            $user->save();
            DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.notifications.saved', $context));
        });

        return Response::make(true, __('kanban.saved'), ['preferences' => $this->preferences($actor->fresh())]);
    }

    /**
     * Send one deduplicated reminder for each upcoming, still-visible assignment.
     *
     * @return int
     */
    public function sendDeadlineReminders(): int
    {
        $sent = 0;
        $rows = DB::table('kanban_card_meta')->whereNotNull('assignee_id')->whereBetween('due_at', [now()->utc(), now()->utc()->addDay()])
            ->orderBy('post_id')->limit(1000)->get(['post_id', 'assignee_id', 'due_at']);
        foreach ($rows as $row) {
            $post = Post::query()->find($row->post_id);
            $recipient = User::query()->where('status', true)->find($row->assignee_id);
            if (! $post || ! $recipient || ! PostTypeRegistry::has($post->type)) continue;
            $fallback = $post->type === 'page' ? 'admin.content.pages.view' : 'admin.content.posts.view';
            if (! app(AdminAccess::class)->allows($recipient, PostTypeRegistry::for($post->type)->getViewPermission() ?? $fallback)
                || ! app(PostStateAccess::class)->allows($post, $recipient)) continue;
            $key = 'deadline:'.$post->id.':'.strtotime($row->due_at);
            if (DB::table('kanban_notifications')->where('dedupe_key', $key)->exists()) continue;
            if ($this->send((int) $recipient->id, (int) $post->id, 'deadline', null, $key)) $sent++;
        }
        return $sent;
    }
}
