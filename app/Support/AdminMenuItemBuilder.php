<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use InvalidArgumentException;

/**
 * Define an admin navigation item and its children through a fluent API.
 */
final class AdminMenuItemBuilder
{
    /**
     * @var array<string, mixed>
     */
    private array $definition = [];

    /**
     * Create a builder for one menu item.
     *
     * @param string $key
     * @param object $parent
     * @param Closure $commit
     *
     * @return void
     */
    public function __construct(
        private readonly string $key,
        private readonly object $parent,
        private readonly Closure $commit,
    ) {
    }

    /**
     * Set the item label or a callback that resolves it.
     *
     * @param string|Closure|array $label
     *
     * @return self
     */
    public function label(string|Closure|array $label): self
    {
        return $this->set('label', $label);
    }

    /**
     * Set the item icon.
     *
     * @param string $icon
     *
     * @return self
     */
    public function icon(string $icon): self
    {
        return $this->set('icon', $icon);
    }

    /**
     * Set a named route and its parameters.
     *
     * @param string $route
     * @param array $parameters
     *
     * @return self
     */
    public function route(string $route, array $parameters = []): self
    {
        $this->set('route', $route);

        return $this->set('parameters', $parameters);
    }

    /**
     * Set the item URL or a callback that resolves it.
     *
     * @param string|Closure $url
     *
     * @return self
     */
    public function url(string|Closure $url): self
    {
        return $this->set('url', $url);
    }

    /**
     * Set one or more permissions required to display the item.
     *
     * @param string|array|Closure $permissions
     *
     * @return self
     */
    public function permission(string|array|Closure $permissions): self
    {
        return $this->set('permission', $permissions);
    }

    /**
     * Set one or more permissions required to display the item.
     *
     * @param string|array|Closure $permissions
     *
     * @return self
     */
    public function permissions(string|array|Closure $permissions): self
    {
        return $this->set('permissions', $permissions);
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
        return $this->set('active', $active);
    }

    /**
     * Set whether this item is expanded.
     *
     * @param bool|Closure $open
     *
     * @return self
     */
    public function open(bool|Closure $open = true): self
    {
        return $this->set('open', $open);
    }

    /**
     * Set the visibility condition for this item.
     *
     * @param bool|Closure $visible
     *
     * @return self
     */
    public function visible(bool|Closure $visible): self
    {
        return $this->set('visible', $visible);
    }

    /**
     * Set the item's position relative to sibling items.
     *
     * @param int $order
     *
     * @return self
     */
    public function order(int $order): self
    {
        return $this->set('order', $order);
    }

    /**
     * Set an optional item tag.
     *
     * @param string|Closure|null $tag
     *
     * @return self
     */
    public function tag(string|Closure|null $tag): self
    {
        return $this->set('tag', $tag);
    }

    /**
     * Set whether a separator is displayed after this item.
     *
     * @param bool $separator
     *
     * @return self
     */
    public function separator(bool $separator = true): self
    {
        return $this->set('separator', $separator);
    }

    /**
     * Begin defining a child item.
     *
     * @param string $key
     *
     * @return self
     */
    public function child(string $key): self
    {
        $children = $this->definition['children'] ?? [];
        if ($children instanceof Closure) {
            throw new InvalidArgumentException('Static children cannot be added to an item with dynamic children.');
        }

        return new self($key, $this, function (string $key, array $definition) use (&$children): void {
            $children[$key] = array_replace($children[$key] ?? [], $definition);
            $this->definition['children'] = $children;
        });
    }

    /**
     * Set a callback that defines child items with builders.
     *
     * @param Closure $provider
     *
     * @return self
     */
    public function childrenProvider(Closure $provider): self
    {
        return $this->set('children', static function () use ($provider): array {
            $items = new AdminMenuItemsBuilder(new \stdClass);
            $provider($items);

            return $items->definitions();
        });
    }

    /**
     * Finish this item and return to its parent builder.
     *
     * @return object
     */
    public function end(): object
    {
        ($this->commit)($this->key, $this->definition);

        return $this->parent;
    }

    /**
     * Set a definition key and value.
     *
     * @param string $key
     * @param mixed $value
     *
     * @return self
     */
    private function set(string $key, mixed $value): self
    {
        $this->definition[$key] = $value;

        return $this;
    }
}
