<?php

declare(strict_types=1);

namespace App\Events\Kanban;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe the synchronous Kanban Card Work Saving boundary without exposing board inputs.
 */
readonly class KanbanCardWorkSavingEvent implements DomainEvent
{
    /** @var array<string, int|string|null> */
    public array $context;

    /**
     * Copy only approved scalar scope from the lifecycle context.
     *
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function __construct(array $context)
    {
        $safe = [];
        foreach (['type', 'id', 'post_id', 'user_id', 'operation'] as $key) {
            if (array_key_exists($key, $context) && (is_int($context[$key]) || is_string($context[$key]) || $context[$key] === null)) {
                $safe[$key] = $context[$key];
            }
        }
        $this->context = $safe;
    }

    /**
     * Return the stable lifecycle key.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.posts.kanban.card_work.saving';
    }

    /**
     * Expose discovery metadata without enabling automation.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'lifecycle.jobic_posts_kanban_card_work_saving.group', 'lifecycle.jobic_posts_kanban_card_work_saving.title', 'lifecycle.jobic_posts_kanban_card_work_saving.description', 'fas fa-shield-alt', [
            'lifecycle', 'synchronous', 'phase:before', 'automation:disabled',
        ]);
    }
}
