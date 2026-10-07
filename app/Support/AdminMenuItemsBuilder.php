<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Collect fluent item definitions for a section or a dynamic children provider.
 */
final class AdminMenuItemsBuilder
{
    /**
     * @var array
     */
    private array $items = [];

    /**
     * Create a collection builder for menu items.
     *
     * @param object $parent
     *
     * @return void
     */
    public function __construct(private readonly object $parent)
    {
    }

    /**
     * Begin defining an item in this collection.
     *
     * @param string $key
     *
     * @return AdminMenuItemBuilder
     */
    public function item(string $key): AdminMenuItemBuilder
    {
        return new AdminMenuItemBuilder($key, $this, function (string $key, array $definition): void {
            $this->items[$key] = array_replace($this->items[$key] ?? [], $definition);
        });
    }

    /**
     * Get the item definitions collected by this builder.
     *
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        return $this->items;
    }

    /**
     * Return to the parent builder.
     *
     * @return object
     */
    public function end(): object
    {
        return $this->parent;
    }
}
