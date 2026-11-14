<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/** Describe a Kanban committed business outcome with safe technical identifiers. */
readonly class KanbanCardWorkSavedEvent implements DomainEvent
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
        return 'jobic.posts.kanban.card_work.saved';
    }

    /**
     * Describe the event for the shared registry.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.jobic_posts_kanban_card_work_saved.group', 'events.jobic_posts_kanban_card_work_saved.title', 'events.jobic_posts_kanban_card_work_saved.description', 'fas fa-columns', ['jobic', 'kanban', 'phase:committed']);
    }
}
