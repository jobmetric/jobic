<?php

declare(strict_types=1);

namespace App\Events\Language;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a committed language deactivate outcome without sensitive data.
 */
readonly class LanguageDeactivateEvent implements DomainEvent
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
        return 'jobic.language.deactivated';
    }

    /**
     * Describe this outcome for discovery and translated automation interfaces.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.language.group', 'events.language.deactivated.title', 'events.language.deactivated.description', 'fas fa-user', [
            'jobic',
            'language',
        ]);
    }
}
