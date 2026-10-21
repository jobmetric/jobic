<?php

declare(strict_types=1);

namespace App\Events\TwoFactor;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a successful TwoFactor outcome using only approved identifiers.
 */
readonly class TwoFactorDisableEvent implements DomainEvent
{
    /**
     * Capture safe identifiers for committed outcome consumers.
     *
     * @param int $id
     *
     * @return void
     */
    public function __construct(public int $id)
    {
    }

    /**
     * Return the stable key of this business outcome.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.two_factor.disabled';
    }

    /**
     * Describe the translated outcome for registry discovery.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.two_factor_disabled.group', 'events.two_factor_disabled.title', 'events.two_factor_disabled.description', 'fas fa-bolt', [
            'jobic',
            'automation',
            'committed',
        ]);
    }
}
