<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\View;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

/** Register, authorize, resolve, and render the three-level admin navigation tree. */
class AdminMenuRegistry
{
    /**
     * @var array
     */
    private array $sections = [];

    /**
     * @var array
     */
    private array $providers = [];

    /**
     * @var array|null
     */
    private ?array $resolvedSections = null;

    /**
     * @var string|null
     */
    private ?string $resolvedContextKey = null;

    public function __construct(private readonly AdminAccess $access)
    {
    }

    /**
     * Register a top-level section in the admin menu.
     *
     * @param string $key
     *
     * @return AdminMenuSectionBuilder
     */
    public function register(string $key): AdminMenuSectionBuilder
    {
        $this->validateKey($key);
        $this->sections[$key] ??= ['items' => []];
        $this->invalidate();

        return new AdminMenuSectionBuilder($this, $key);
    }

    /**
     * Set an option on a registered section.
     *
     * @param string $section
     * @param string $option
     * @param mixed $value
     *
     * @return void
     */
    public function setSectionOption(string $section, string $option, mixed $value): void
    {
        $this->assertSection($section);
        $this->sections[$section][$option] = $value;
        $this->invalidate();
    }

    /**
     * Add a builder-defined item to a registered section.
     *
     * @param string $section
     * @param string $key
     * @param array $definition
     *
     * @return void
     */
    public function addSectionItem(string $section, string $key, array $definition): void
    {
        $this->assertSection($section);
        $this->validateKey($key);
        $this->validateDefinition($definition);
        $this->assertMaximumDepth($definition, 2);
        $this->sections[$section]['items'][$key] = array_replace($this->sections[$section]['items'][$key] ?? [], $definition);
        $this->invalidate();
    }

    /**
     * Add a provider that defines additional section items with builders.
     *
     * @param string $section
     * @param Closure $provider
     *
     * @return void
     */
    public function addSectionProvider(string $section, Closure $provider): void
    {
        $this->assertSection($section);
        $this->providers[$section][] = static function () use ($provider): array {
            $items = new AdminMenuItemsBuilder(new \stdClass);
            $provider($items);

            return $items->definitions();
        };
        $this->invalidate();
    }

    /**
     * Resolve the menu tree for the current request, filtering out items that are not visible to the current user.
     *
     * @return array|mixed[][]
     */
    public function sections(): array
    {
        $contextKey = implode(':', [
            spl_object_id(request()),
            (string) (auth()->id() ?? 0),
            app()->getLocale(),
            (string) request()
                ->route()
                ?->getName(),
            (string) request()->route('type', ''),
        ]);

        if ($this->resolvedSections !== null && $this->resolvedContextKey === $contextKey) {
            return $this->resolvedSections;
        }

        $sections = [];
        foreach ($this->sections as $key => $section) {
            if (! $this->isVisible($section)) {
                continue;
            }

            $definitions = $section['items'] ?? [];
            foreach ($this->providers[$key] ?? [] as $provider) {
                foreach ((array) $provider() as $itemKey => $item) {
                    if (! is_array($item)) {
                        continue;
                    }

                    $itemKey = (string) ($item['key'] ?? $itemKey);
                    $definitions[$itemKey] = array_replace($definitions[$itemKey] ?? [], $item);
                }
            }

            $items = [];
            foreach ($definitions as $itemKey => $item) {
                $resolved = $this->resolveItem((string) $itemKey, $item, 2);
                if ($resolved !== null) {
                    $items[] = $resolved;
                }
            }

            $items = $this->sortItems($items);
            if ($items === []) {
                continue;
            }

            $resolved = $this->resolveCommon($key, $section);
            $resolved['items'] = $items;
            $resolved['active'] = $resolved['active'] || collect($items)->contains(fn (array $item): bool => $item['active'] || $item['open']);
            $resolved['sideblock'] = $section['sideblock'] ?? 'group';
            $sections[] = $resolved;
        }

        $this->resolvedContextKey = $contextKey;

        return $this->resolvedSections = $this->sortItems($sections);
    }

    /**
     * Get the resolved items for a section, filtering out items that are not visible to the current user.
     *
     * @param string $section
     *
     * @return array
     */
    public function items(string $section): array
    {
        foreach ($this->sections() as $resolved) {
            if ($resolved['key'] === $section) {
                return $resolved['items'];
            }
        }

        return [];
    }

    /**
     * Render the menu tree for the current request, filtering out items that are not visible to the current user.
     *
     * @param string|null $section
     *
     * @return HtmlString
     */
    public function render(?string $section = null): HtmlString
    {
        if ($section === null) {
            return new HtmlString(View::make('common.navigation.admin-menu-primary', [
                'sections' => $this->sections(),
            ])
                ->render());
        }

        return new HtmlString(View::make('components.vuero.navigation.menu', [
            'items' => $this->items($section),
        ])
            ->render());
    }

    /**
     * Resolve an item or section, filtering out items that are not visible to the current user.
     *
     * @param string $key
     * @param array $definition
     * @param int $level
     *
     * @return array|null
     */
    private function resolveItem(string $key, array $definition, int $level): ?array
    {
        if ($level > 3) {
            throw new InvalidArgumentException('Admin navigation supports no more than three levels.');
        }

        if (! $this->isVisible($definition)) {
            return null;
        }

        $children = $definition['children'] ?? [];
        if ($children instanceof Closure) {
            $children = $children();
        }

        $resolvedChildren = [];
        foreach ((array) $children as $childKey => $child) {
            if (! is_array($child)) {
                continue;
            }

            $childKey = (string) ($child['key'] ?? $childKey);
            $resolved = $this->resolveItem($childKey, $child, $level + 1);
            if ($resolved !== null) {
                $resolvedChildren[] = $resolved;
            }
        }
        $resolvedChildren = $this->sortItems($resolvedChildren);

        if (($definition['children'] ?? null) !== null && $resolvedChildren === []) {
            return null;
        }

        $resolved = $this->resolveCommon($key, $definition);
        $resolved['children'] = $resolvedChildren;
        $resolved['open'] = $resolved['open'] || collect($resolvedChildren)->contains(fn (array $child): bool => $child['active'] || $child['open']);

        return $resolved;
    }

    /**
     * Resolve common properties for an item or section, such as label, icon, url, active state, and order.
     *
     * @param string $key
     * @param array $definition
     *
     * @return array
     */
    private function resolveCommon(string $key, array $definition): array
    {
        $label = $definition['label'] ?? $key;
        $translateLabel = ! is_callable($label);
        if (is_callable($label)) {
            $label = $label();
        }

        while (is_array($label)) {
            $locale = app()->getLocale();
            $label = $label[$locale] ?? $label[strtolower(substr($locale, 0, 2))] ?? $label['label'] ?? $label['name'] ?? reset($label) ?? $key;
        }

        if (is_string($label) || $label instanceof \Stringable) {
            $label = (string) $label;
            if ($translateLabel) {
                $translated = trans($label);
                if (is_string($translated)) {
                    $label = $translated;
                }
            }
        } else {
            $label = $key;
        }

        $url = $definition['url'] ?? null;
        if (is_callable($url)) {
            $url = $url();
        } else if (isset($definition['route'])) {
            $url = route((string) $definition['route'], $definition['parameters'] ?? []);
        }

        $active = $definition['active'] ?? false;
        if (is_callable($active)) {
            $active = (bool) $active();
        } else if (is_string($active)) {
            $active = request()->routeIs($active);
        }

        $open = $definition['open'] ?? false;
        if (is_callable($open)) {
            $open = (bool) $open();
        }

        return [
            'key'       => $key,
            'label'     => (string) $label,
            'icon'      => (string) ($definition['icon'] ?? 'activity'),
            'url'       => is_string($url) ? $url : null,
            'active'    => (bool) $active,
            'open'      => (bool) $open,
            'order'     => (int) ($definition['order'] ?? 0),
            'tag'       => $definition['tag'] ?? null,
            'separator' => (bool) ($definition['separator'] ?? false),
        ];
    }

    /**
     * Determine if a definition is visible to the current user based on its visibility and permissions.
     *
     * @param array $definition
     *
     * @return bool
     */
    private function isVisible(array $definition): bool
    {
        $visible = $definition['visible'] ?? true;
        if (is_callable($visible) && ! $visible()) {
            return false;
        }
        if (! $visible) {
            return false;
        }

        $permissions = $definition['permissions'] ?? $definition['permission'] ?? null;
        if ($permissions === null || $permissions === '') {
            return true;
        }

        return $this->access->allowsAny(auth()->user(), (array) $permissions);
    }

    /**
     * Validate that a definition has a label and that its children are an array or a closure.
     *
     * @param array $definition
     *
     * @return void
     */
    private function validateDefinition(array $definition): void
    {
        if (! array_key_exists('label', $definition)) {
            throw new InvalidArgumentException('Admin menu entries require a label.');
        }
        if (isset($definition['children']) && ! is_array($definition['children']) && ! $definition['children'] instanceof Closure) {
            throw new InvalidArgumentException('Admin menu children must be an array or a closure.');
        }
    }

    /**
     * Assert that a definition does not exceed the maximum depth of three levels.
     *
     * @param array $definition
     * @param int $level
     *
     * @return void
     */
    private function assertMaximumDepth(array $definition, int $level): void
    {
        if ($level > 3) {
            throw new InvalidArgumentException('Admin navigation supports no more than three levels.');
        }
        if (($definition['children'] ?? null) instanceof Closure) {
            return;
        }
        foreach ($definition['children'] ?? [] as $child) {
            if (is_array($child)) {
                $this->assertMaximumDepth($child, $level + 1);
            }
        }
    }

    /**
     * Assert that a section is registered in the admin menu.
     *
     * @param string $section
     *
     * @return void
     */
    private function assertSection(string $section): void
    {
        if (! isset($this->sections[$section])) {
            throw new InvalidArgumentException("Admin menu section [{$section}] is not registered.");
        }
    }

    /**
     * Validate that a key is non-empty and does not contain dots.
     *
     * @param string $key
     *
     * @return void
     */
    private function validateKey(string $key): void
    {
        if ($key === '' || str_contains($key, '.')) {
            throw new InvalidArgumentException('Admin menu keys must be non-empty and may not contain dots.');
        }
    }

    /**
     * Invalidate the resolved sections and context key, forcing a re-resolution on the next request.
     *
     * @return void
     */
    private function invalidate(): void
    {
        $this->resolvedSections = null;
        $this->resolvedContextKey = null;
    }

    /**
     * Sort an array of items by their order property.
     *
     * @param array $items
     *
     * @return array
     */
    private function sortItems(array $items): array
    {
        usort($items, static fn (array $left, array $right): int => $left['order'] <=> $right['order']);

        return $items;
    }
}
