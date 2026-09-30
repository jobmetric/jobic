<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Register the CMS permission catalog and its Rolix adapter.
 */
class PermissionsDriver implements ApplicationDriver
{
    /**
     * Register the shared permission registry.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(PermissionRegistry::class);
    }

    /**
     * Seed the core CMS permission definitions.
     *
     * @param Application $app
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(Application $app): void
    {
        $permissions = $app->make(PermissionRegistry::class);
        $permissions->addPermission('admin', 'roles.permissions.admin')
            ->addPermission('admin.dashboard', 'roles.permissions.dashboard', 'admin.dashboard', order: 10)
            ->addPermission('admin.users', 'roles.permissions.users')
            ->addPermission('admin.users.view', 'roles.permissions.users_view', 'admin.users.index', order: 20)
            ->addPermission('admin.users.manage', 'roles.permissions.users_manage', 'admin.users.index', order: 21)
            ->addPermission('admin.roles', 'roles.permissions.roles')
            ->addPermission('admin.roles.view', 'roles.permissions.roles_view', 'admin.roles.index', order: 30)
            ->addPermission('admin.roles.manage', 'roles.permissions.roles_manage', 'admin.roles.index', order: 31)
            ->addPermission('admin.content', 'roles.permissions.content')
            ->addPermission('admin.menus.view', 'roles.permissions.menus_view', 'admin.menus.index', order: 35)
            ->addPermission('admin.menus.manage', 'roles.permissions.menus_manage', 'admin.menus.index', order: 36)
            ->addPermission('admin.settings', 'roles.permissions.settings', 'admin.settings.index', order: 40)
            ->addPermission('admin.settings.general', 'roles.permissions.settings_general', 'admin.settings.edit', ['section' => 'general'], 41)
            ->addPermission('admin.settings.seo', 'roles.permissions.settings_seo', 'admin.settings.edit', ['section' => 'seo'], 42)
            ->addPermission('admin.settings.health', 'roles.permissions.settings_health', 'admin.settings.health', order: 43)
            ->addPermission('admin.settings.cache', 'roles.permissions.settings_cache', 'admin.settings.cache.edit', order: 44)
            ->addPermission('admin.settings.languages', 'roles.permissions.settings_languages', 'admin.settings.languages.index', order: 45)
            ->addPermission('admin.settings.mail', 'roles.permissions.settings_mail', 'admin.settings.mail.edit', order: 46);
    }
}
