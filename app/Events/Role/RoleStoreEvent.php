<?php

declare(strict_types=1);

namespace App\Events\Role;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a committed role store outcome without sensitive data.
 */
readonly class RoleStoreEvent implements DomainEvent
{
    /**
     * Capture only the affected record and the actor identifiers.
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
        return 'jobic.role.stored';
    }

    /**
     * Describe this outcome for discovery and translated automation interfaces.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.role.group', 'events.role.stored.title', 'events.role.stored.description', 'fas fa-user', [
            'jobic',
            'role',
        ]);
    }
}
