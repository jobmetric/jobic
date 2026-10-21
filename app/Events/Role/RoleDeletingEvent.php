<?php

declare(strict_types=1);

namespace App\Events\Role;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe the synchronous Role Deleting boundary without exposing persistence inputs.
 */
readonly class RoleDeletingEvent implements DomainEvent
{
    /** @var array<string, int|string|null> */
    public array $context;

    /**
     * Copy only approved scalar scope, excluding models, input arrays and field values.
     *
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function __construct(array $context)
    {
        $safe = [];
        foreach (
            [
                'id',
                'post_id',
                'revision_id',
                'user_id',
                'owner_id',
                'owner_type',
                'type',
                'locale',
                'field',
                'section',
                'operation',
                'application',
                'key',
                'form_name',
            ] as $key) {
            if (array_key_exists($key, $context) && (is_int($context[$key]) || is_string($context[$key]) || $context[$key] === null)) {
                $safe[$key] = $context[$key];
            }
        }
        $this->context = $safe;
    }

    /**
     * Return the stable lifecycle key shared with the compatibility hook.
     *
     * @return string
     */
    public static function key(): string
    {
        return 'jobic.roles.deleting';
    }

    /**
     * Expose discovery metadata while excluding this boundary from automation triggers.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'lifecycle.jobic_roles_deleting.group', 'lifecycle.jobic_roles_deleting.title', 'lifecycle.jobic_roles_deleting.description', 'fas fa-shield-alt', [
            'lifecycle',
            'synchronous',
            'phase:before',
            'automation:disabled',
        ]);
    }
}
