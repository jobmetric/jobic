<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use App\Services\Settings\DynamicSettings;

/**
 * Resolve hierarchical CMS permissions from effective Rolix grants and denials.
 */
class AdminAccess
{
    /**
     * Resolve the first useful administration route available to a user.
     */
    public function landingRoute(?User $user): string
    {
        foreach ($this->dynamicSettingDescriptors() as $setting) {
            if ($this->allows($user, $this->dynamicSettingsPermission($setting['application'], $setting['key']))) {
                return 'admin.settings.index';
            }
        }

        $destinations = [
            'admin.dashboard'                => ['admin.dashboard'],
            'admin.users.index'              => ['admin.users.view'],
            'admin.roles.index'              => ['admin.roles.view'],
            'admin.settings.index'           => [
                'admin.settings',
                'admin.settings.general',
                'admin.settings.seo',
                'admin.settings.health',
                'admin.settings.cache',
                'admin.settings.languages',
                'admin.settings.mail',
            ],
            'admin.settings.edit'            => ['admin.settings.general'],
            'admin.settings.health'          => ['admin.settings.health'],
            'admin.settings.cache.edit'      => ['admin.settings.cache'],
            'admin.settings.languages.index' => ['admin.settings.languages'],
            'admin.settings.mail.edit'       => ['admin.settings.mail'],
        ];

        foreach ($destinations as $route => $permissions) {
            if ($this->allowsAny($user, $permissions)) {
                return $route;
            }
        }

        return 'admin.dashboard';
    }

    /**
     * Determine whether a user may use one CMS permission key.
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
     * @param array<int, string> $permissions
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
     */
    public function settingsPermission(string $section): string
    {
        return match ($section) {
            'general' => 'admin.settings.general',
            'seo' => 'admin.settings.seo',
            'health' => 'admin.settings.health',
            'cache' => 'admin.settings.cache',
            'languages' => 'admin.settings.languages',
            'mail' => 'admin.settings.mail',
            default => 'admin.settings',
        };
    }

    /**
     * Build the permission protecting one discovered settings form.
     */
    public function dynamicSettingsPermission(string $application, string $key): string
    {
        return app(DynamicSettings::class)->permission($application, $key);
    }

    /** Determine whether a user can access at least one discovered settings form. */
    public function allowsAnyDynamicSetting(?User $user): bool
    {
        foreach ($this->dynamicSettingDescriptors() as $setting) {
            if ($this->allows($user, $this->dynamicSettingsPermission($setting['application'], $setting['key']))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read dynamic descriptors when the supporting package version is installed.
     *
     * @return array<int, array<string, string>>
     */
    private function dynamicSettingDescriptors(): array
    {
        return app(DynamicSettings::class)->descriptors();
    }

    /**
     * Match exact, wildcard, or inherited parent permissions.
     *
     * @param array<int, string> $entries
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
        }

        return false;
    }
}
