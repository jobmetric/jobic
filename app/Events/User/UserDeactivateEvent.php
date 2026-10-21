<?php

declare(strict_types=1);

namespace App\Events\User;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a committed user deactivate outcome without account credentials.
 */
readonly class UserDeactivateEvent implements DomainEvent
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
        return 'jobic.user.deactivated';
    }

    /**
     * Describe this outcome for discovery and translated automation interfaces.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.user.group', 'events.user.deactivated.title', 'events.user.deactivated.description', 'fas fa-user', [
            'jobic',
            'user',
        ]);
    }
}
