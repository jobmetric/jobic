<?php

declare(strict_types=1);

namespace App\Events\User;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a committed user store outcome without account credentials.
 */
readonly class UserStoreEvent implements DomainEvent
{
    /**
     * Capture only the affected account and the actor identifiers.
     *
     * @param int $id
     * @param int $actorId
     *
     * @return void
     */
    public function __construct(public int $id, public int $actorId)
    {
    }

    /**
     * Return the stable automation key for this outcome.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.user.stored';
    }

    /**
     * Describe this outcome for discovery and translated automation interfaces.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.user.group', 'events.user.stored.title', 'events.user.stored.description', 'fas fa-user', [
            'jobic',
            'user',
        ]);
    }
}
