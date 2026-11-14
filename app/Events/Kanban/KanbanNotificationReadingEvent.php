<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/** Describe a Kanban synchronous lifecycle boundary with safe technical identifiers. */
readonly class KanbanNotificationReadingEvent implements DomainEvent
{
    /** @var array<string, int|string|null> */
    public array $context;

    /**
     * Keep only approved scalar identifiers for discovery and automation.
     *
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function __construct(array $context)
    {
        $safe = [];
        foreach (['type', 'id', 'post_id', 'comment_id', 'revision_id', 'user_id', 'operation'] as $key) {
            if (array_key_exists($key, $context) && (is_int($context[$key]) || is_string($context[$key]) || $context[$key] === null)) {
                $safe[$key] = $context[$key];
            }
        }
        $this->context = $safe;
    }

    /**
     * Return the stable event key shared with its legacy hook.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.posts.kanban.notification.reading';
    }

    /**
     * Describe the event for the shared registry.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'lifecycle.jobic_posts_kanban_notification_reading.group', 'lifecycle.jobic_posts_kanban_notification_reading.title', 'lifecycle.jobic_posts_kanban_notification_reading.description', 'fas fa-columns', ['lifecycle', 'synchronous', 'phase:before', 'automation:disabled']);
    }
}
