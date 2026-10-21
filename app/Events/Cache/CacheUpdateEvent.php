<?php

declare(strict_types=1);

namespace App\Events\Cache;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a successful Cache outcome using only approved identifiers.
 */
readonly class CacheUpdateEvent implements DomainEvent
{
    /**
     * Capture safe identifiers for committed outcome consumers.
     *
     * @param int|null $actorId
     *
     * @return void
     */
    public function __construct(public ?int $actorId)
    {
    }

    /**
     * Return the stable key of this business outcome.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.cache.updated';
    }

    /**
     * Describe the translated outcome for registry discovery.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.cache_updated.group', 'events.cache_updated.title', 'events.cache_updated.description', 'fas fa-bolt', [
            'jobic',
            'automation',
            'committed',
        ]);
    }
}
