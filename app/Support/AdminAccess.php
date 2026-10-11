<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Services\Settings\DynamicSettings;
use App\Support\Permissions\PermissionRegistry;

/**
 * Resolve hierarchical CMS permissions from effective Rolix grants and denials.
 */
readonly class AdminAccess
{
    /**
     * Create the centralized CMS access decision service.
     *
     * @param PermissionRegistry $permissions
     *
     * @return void
     */
    public function __construct(private PermissionRegistry $permissions)
    {
    }

    /**
     * Resolve the first useful administration route available to a user.
     *
     * @param User|null $user
     *
     * @return string
     */
    public function landingRoute(?User $user): string
    {
        return $this->landingDefinition($user)['route'];
    }

    /**
     * Resolve route parameters for the user's first authorized CMS destination.
     *
     * @param User|null $user
     *
     * @return array<string, scalar>
     */
    public function landingParameters(?User $user): array
    {
        return $this->landingDefinition($user)['parameters'];
    }

    /**
     * Determine whether a user may use one CMS permission key.
     *
     * @param User|null $user
     * @param string $permission
     *
     * @return bool
     */
    public function allows(?User $user, string $permission): bool
    {
        if ($user === null) {
            return false;
        }

        $permissions = $user->getPermissions();

        if ($this->matches($permission, $permissions['deny'])) {
            return false;
        }

        return $this->matches($permission, $permissions['allow']);
    }

    /**
     * Determine whether a user may use at least one CMS permission key.
     *
     * @param User|null $user
     * @param array<int, string> $permissions
     *
     * @return bool
     */
    public function allowsAny(?User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->allows($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the permission key protecting a settings section.
     *
     * @param string $section
     *
     * @return string
     */
    public function settingsPermission(string $section): string
    {
        $permission = 'admin.settings.' . strtolower($section);

        return $this->permissions->has($permission) ? $permission : 'admin.settings';
    }

    /**
     * Build the permission protecting one discovered settings form.
     *
     * @param string $application
     * @param string $key
     *
     * @return string
     */
    public function dynamicSettingsPermission(string $application, string $key): string
    {
        return app(DynamicSettings::class)->permission($application, $key);
    }

    /**
     * Determine whether a user can access any registered permission in a namespace.
     *
     * @param User|null $user
     * @param string $prefix
     *
     * @return bool
     */
    public function allowsAnyRegistered(?User $user, string $prefix): bool
    {
        return $this->allowsAny($user, $this->permissions->beneath($prefix));
    }

    /**
     * Resolve the first registered landing definition allowed for a user.
     *
     * @param User|null $user
     *
     * @return array{route: string, parameters: array<string, scalar>}
     */
    private function landingDefinition(?User $user): array
    {
        foreach ($this->permissions->landingPermissions() as $permission => $definition) {
            if ($this->allows($user, $permission)) {
                return [
                    'route'      => (string) $definition['route'],
                    'parameters' => $definition['parameters'],
                ];
            }
        }

        return [
            'route'      => 'admin.dashboard',
            'parameters' => [],
        ];
    }

    /**
     * Match exact, wildcard, or inherited parent permissions.
     *
     * @param string $permission
     * @param array<int, string> $entries
     *
     * @return bool
     */
    private function matches(string $permission, array $entries): bool
    {
        foreach ($entries as $entry) {
            if ($entry === '*' || $entry === $permission) {
                return true;
            }

            if (str_ends_with($entry, '.*') && str_starts_with($permission, substr($entry, 0, -1))) {
                return true;
            }

            if (str_starts_with($permission, $entry . '.')) {
                return true;
            }

            if ($this->permissions->groupCovers($entry, $permission)) {
                return true;
            }
        }

        return false;
    }
}
