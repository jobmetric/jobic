<?php

declare(strict_types=1);

namespace App\Events\Language;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a committed language store outcome without sensitive data.
 */
readonly class LanguageStoreEvent implements DomainEvent
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
        return 'jobic.language.stored';
    }

    /**
     * Describe this outcome for discovery and translated automation interfaces.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.language.group', 'events.language.stored.title', 'events.language.stored.description', 'fas fa-user', [
            'jobic',
            'language',
        ]);
    }
}
