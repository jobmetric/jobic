<?php

declare(strict_types=1);

namespace App\Events\Auth;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe a successful Auth outcome using only approved identifiers.
 */
readonly class AuthPasswordResetEvent implements DomainEvent
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
        return 'jobic.auth.password_reset';
    }

    /**
     * Describe the translated outcome for registry discovery.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'events.auth_password_reset.group', 'events.auth_password_reset.title', 'events.auth_password_reset.description', 'fas fa-bolt', [
            'jobic',
            'automation',
            'committed',
        ]);
    }
}
