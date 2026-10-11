<?php

declare(strict_types=1);

namespace App\Support\Permissions;

use InvalidArgumentException;

/**
 * Store the authoritative catalog of CMS permissions and landing targets.
 */
class PermissionRegistry
{
    /** @var array<string, array{label: string, route: string|null, parameters: array<string, scalar>, order: int}> */
    private array $permissions = [];

    /** @var array<string, list<string>> */
    private array $groups = [];

    /**
     * Register a permission that grants or denies several existing namespaces.
     *
     * @param string $key
     * @param string $label
     * @param list<string> $members
     *
     * @return self
     */
    public function addGroupPermission(string $key, string $label, array $members): self
    {
        $this->addPermission($key, $label);
        if (isset($this->groups[$key]) && $this->groups[$key] !== $members) {
            throw new InvalidArgumentException("Permission group [{$key}] is already registered with different members.");
        }

        $this->groups[$key] = $members;

        return $this;
    }

    /**
     * Determine whether a group permission covers a requested key.
     *
     * @param string $group
     * @param string $permission
     *
     * @return bool
     */
    public function groupCovers(string $group, string $permission): bool
    {
        foreach ($this->groups[$group] ?? [] as $member) {
            if ($permission === $member || str_starts_with($permission, $member . '.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add one permission definition to the application catalog.
     *
     * Registering the exact same definition is idempotent; conflicting definitions
     * are rejected so extensions cannot silently replace another owner's policy.
     *
     * @param string $key
     * @param string $label
     * @param string|null $route
     * @param array<string, scalar> $parameters
     * @param int $order
     *
     * @return self
     */
    public function addPermission(
        string $key,
        string $label,
        ?string $route = null,
        array $parameters = [],
        int $order = 100
    ): self {
        if (! preg_match('/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*$/', $key)) {
            throw new InvalidArgumentException("Invalid permission key [{$key}].");
        }

        if ($label === '') {
            throw new InvalidArgumentException("Permission [{$key}] requires a label.");
        }

        $definition = compact('label', 'route', 'parameters', 'order');
        if (isset($this->permissions[$key]) && $this->permissions[$key] !== $definition) {
            throw new InvalidArgumentException("Permission [{$key}] is already registered with a different definition.");
        }

        $this->permissions[$key] = $definition;

        return $this;
    }

    /**
     * Determine whether a permission has been registered.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->permissions[$key]);
    }

    /**
     * Return the flat key-to-label map consumed by Rolix.
     *
     * @return array<string, string>
     */
    public function labels(): array
    {
        return array_map(static fn (array $definition): string => $definition['label'], $this->permissions);
    }

    /**
     * Return registered keys below a permission namespace, including the namespace itself.
     *
     * @param string $prefix
     *
     * @return list<string>
     */
    public function beneath(string $prefix): array
    {
        return array_values(array_filter(array_keys($this->permissions), static fn (string $key): bool => $key === $prefix || str_starts_with($key, $prefix . '.')));
    }

    /**
     * Return ordered permissions that declare a valid CMS landing route.
     *
     * @return array<string, array{label: string, route: string|null, parameters: array<string, scalar>, order: int}>
     */
    public function landingPermissions(): array
    {
        $permissions = array_filter($this->permissions, static fn (array $definition): bool => $definition['route'] !== null);
        uasort($permissions, static fn (array $left, array $right): int => $left['order'] <=> $right['order']);

        return $permissions;
    }
}
