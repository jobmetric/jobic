<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

/**
 * Define a top-level admin navigation section through a fluent API.
 */
final readonly class AdminMenuSectionBuilder
{
    /**
     * Create a builder for one menu section.
     *
     * @param AdminMenuRegistry $registry
     * @param string $key
     *
     * @return void
     */
    public function __construct(private AdminMenuRegistry $registry, private string $key)
    {
    }

    /**
     * Set the section label or a callback that resolves it.
     *
     * @param string|Closure|array $label
     *
     * @return self
     */
    public function label(string|Closure|array $label): self
    {
        $this->registry->setSectionOption($this->key, 'label', $label);

        return $this;
    }

    /**
     * Set the section icon.
     *
     * @param string $icon
     *
     * @return self
     */
    public function icon(string $icon): self
    {
        $this->registry->setSectionOption($this->key, 'icon', $icon);

        return $this;
    }

    /**
     * Set one or more permissions required to display the section.
     *
     * @param string|array $permissions
     *
     * @return self
     */
    public function permission(string|array $permissions): self
    {
        $this->registry->setSectionOption($this->key, 'permission', $permissions);

        return $this;
    }

    /**
     * Set one or more permissions required to display the section.
     *
     * @param string|array $permissions
     *
     * @return self
     */
    public function permissions(string|array $permissions): self
    {
        $this->registry->setSectionOption($this->key, 'permissions', $permissions);

        return $this;
    }

    /**
     * Set the active route pattern or its resolver.
     *
     * @param string|bool|Closure $active
     *
     * @return self
     */
    public function active(string|bool|Closure $active): self
    {
        $this->registry->setSectionOption($this->key, 'active', $active);

        return $this;
    }

    /**
     * Set the visibility condition for this section.
     *
     * @param bool|Closure $visible
     *
     * @return self
     */
    public function visible(bool|Closure $visible): self
    {
        $this->registry->setSectionOption($this->key, 'visible', $visible);

        return $this;
    }

    /**
     * Set the section position relative to sibling sections.
     *
     * @param int $order
     *
     * @return self
     */
    public function order(int $order): self
    {
        $this->registry->setSectionOption($this->key, 'order', $order);

        return $this;
    }

    /**
     * Set how the section is displayed in the side block.
     *
     * @param string $sideblock
     *
     * @return self
     */
    public function sideblock(string $sideblock): self
    {
        $this->registry->setSectionOption($this->key, 'sideblock', $sideblock);

        return $this;
    }

    /**
     * Begin defining an item in this section.
     *
     * @param string $key
     *
     * @return AdminMenuItemBuilder
     */
    public function item(string $key): AdminMenuItemBuilder
    {
        return new AdminMenuItemBuilder($key, $this, fn (string $itemKey, array $definition) => $this->registry->addSectionItem($this->key, $itemKey, $definition));
    }

    /**
     * Add a callback that defines additional section items with builders.
     *
     * @param Closure $provider
     *
     * @return self
     */
    public function provider(Closure $provider): self
    {
        $this->registry->addSectionProvider($this->key, $provider);

        return $this;
    }

    /**
     * Finish this section and return to the registry.
     *
     * @return AdminMenuRegistry
     */
    public function end(): AdminMenuRegistry
    {
        return $this->registry;
    }
}
