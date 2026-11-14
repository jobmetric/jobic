<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;
use JobMetric\EventSystem\Contracts\DomainEvent;

/** Resolve committed Kanban hook names to discoverable domain outcomes. */
final class KanbanOutcomeEvents
{
    /** @var array<string, class-string<DomainEvent>> */
    public const CLASSES = [
        'jobic.posts.kanban.boards.saved' => \App\Events\Kanban\KanbanBoardsSavedEvent::class,
        'jobic.posts.kanban.boards.deleted' => \App\Events\Kanban\KanbanBoardsDeletedEvent::class,
        'jobic.posts.kanban.limits.saved' => \App\Events\Kanban\KanbanLimitsSavedEvent::class,
        'jobic.posts.kanban.settings.saved' => \App\Events\Kanban\KanbanSettingsSavedEvent::class,
        'jobic.posts.kanban.card_work.saved' => \App\Events\Kanban\KanbanCardWorkSavedEvent::class,
        'jobic.posts.kanban.moved' => \App\Events\Kanban\KanbanMovedEvent::class,
        'jobic.posts.kanban.comment.saved' => \App\Events\Kanban\KanbanCommentSavedEvent::class,
        'jobic.posts.kanban.review.saved' => \App\Events\Kanban\KanbanReviewSavedEvent::class,
        'jobic.posts.kanban.notifications.saved' => \App\Events\Kanban\KanbanNotificationsSavedEvent::class,
        'jobic.posts.kanban.notification.sent' => \App\Events\Kanban\KanbanNotificationSentEvent::class,
        'jobic.posts.kanban.notification.read' => \App\Events\Kanban\KanbanNotificationReadEvent::class,
    ];

    /**
     * Build one immutable event from an authorized, committed hook context.
     *
     * @param string $key
     * @param array<string, mixed> $context
     *
     * @return DomainEvent
     * @throws InvalidArgumentException
     */
    public static function make(string $key, array $context): DomainEvent
    {
        $class = self::CLASSES[$key] ?? throw new InvalidArgumentException('Unknown Kanban outcome: '.$key);

        return new $class($context);
    }
}
