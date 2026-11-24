<?php

declare(strict_types=1);

namespace App\Events\Layout;

use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\EventSystem\Support\DomainEventDefinition;

/**
 * Describe the synchronous Layout saving boundary without exposing Module settings.
 */
readonly class LayoutSavingEvent implements DomainEvent
{
    /** @var array<string, int|string|null> */
    public array $context;

    /**
     * Copy only approved scalar layout scope.
     *
     * @param array<string, mixed> $context
     *
     * @return void
     */
    public function __construct(array $context)
    {
        $safe = [];
        foreach (['operation', 'layout_id', 'theme', 'user_id'] as $key) {
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
        return 'jobic.settings.layouts.saving';
    }

    /**
     * Expose discovery metadata while excluding this boundary from automation triggers.
     *
     * @return DomainEventDefinition
     */
    public static function definition(): DomainEventDefinition
    {
        return new DomainEventDefinition(self::key(), 'lifecycle.jobic_settings_layouts_saving.group', 'lifecycle.jobic_settings_layouts_saving.title', 'lifecycle.jobic_settings_layouts_saving.description', 'fas fa-th-large', [
            'lifecycle', 'synchronous', 'phase:before', 'automation:disabled',
        ]);
    }
}
