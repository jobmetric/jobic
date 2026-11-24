<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Support\Permissions\PermissionRegistry;
use App\Support\PostWorkflowPermissions;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use JobMetric\Post\Facades\PostTypeRegistry;

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
        $unlockEditLockPermission = (string) config('admin.permissions.unlock_edit_lock', 'admin.unlock_edit_lock');
        $permissions->addPermission('admin', 'roles.permissions.admin')
            ->addPermission('admin.dashboard', 'roles.permissions.dashboard', 'admin.dashboard', order: 10)
            ->addGroupPermission('admin.people', 'roles.permissions.users', [
                'admin.users',
                'admin.roles',
            ])
            ->addPermission('admin.users', 'roles.permissions.users')
            ->addPermission('admin.users.view', 'roles.permissions.users_view', 'admin.users.index', order: 20)
            ->addPermission('admin.users.manage', 'roles.permissions.users_manage', 'admin.users.index', order: 21)
            ->addPermission('admin.roles', 'roles.permissions.roles')
            ->addPermission('admin.roles.view', 'roles.permissions.roles_view', 'admin.roles.index', order: 30)
            ->addPermission('admin.roles.manage', 'roles.permissions.roles_manage', 'admin.roles.index', order: 31)
            ->addPermission('admin.content', 'roles.permissions.content')
            ->addGroupPermission('admin.content.writings', 'roles.permissions.posts', [
                'admin.content.posts',
                'admin.content.taxonomy_category',
                'admin.content.taxonomy_tag',
            ])
            ->addPermission($unlockEditLockPermission, 'roles.permissions.edit_lock_unlock')
            ->addPermission('admin.content.posts', 'roles.permissions.posts')
            ->addPermission('admin.content.posts.view', 'roles.permissions.posts_view', 'admin.posts.index', ['type' => 'post'], 34)
            ->addPermission('admin.content.posts.view.workflow', 'roles.permissions.posts_workflow_view', 'admin.posts.workflow.index', ['type' => 'post'], 35)
            ->addPermission('admin.content.posts.manage', 'roles.permissions.posts_manage', 'admin.posts.index', ['type' => 'post'], 35)
            ->addPermission('admin.content.posts.manage.workflow', 'roles.permissions.posts_workflow', 'admin.posts.workflow.index', ['type' => 'post'], 36)
            ->addPermission('admin.content.banners', 'posts.banners')
            ->addPermission('admin.content.banners.view', 'posts.banners', 'admin.posts.index', ['type' => 'banner'], 39)
            ->addPermission('admin.content.banners.manage', 'posts.banners_manage', 'admin.posts.index', ['type' => 'banner'], 40)
            ->addPermission('admin.content.pages', 'roles.permissions.pages')
            ->addPermission('admin.content.pages.view', 'roles.permissions.pages_view', 'admin.posts.index', ['type' => 'page'], 36)
            ->addPermission('admin.content.pages.view.workflow', 'roles.permissions.pages_workflow_view', 'admin.posts.workflow.index', ['type' => 'page'], 37)
            ->addPermission('admin.content.pages.manage', 'roles.permissions.pages_manage', 'admin.posts.index', ['type' => 'page'], 37)
            ->addPermission('admin.content.pages.manage.workflow', 'roles.permissions.pages_workflow', 'admin.posts.workflow.index', ['type' => 'page'], 38)
            ->addPermission('admin.content.taxonomy_category', 'roles.permissions.taxonomy_category')
            ->addPermission('admin.content.taxonomy_category.view', 'roles.permissions.taxonomy_category_view', 'admin.taxonomies.index', ['type' => 'category'], 38)
            ->addPermission('admin.content.taxonomy_category.manage', 'roles.permissions.taxonomy_category_manage', 'admin.taxonomies.index', ['type' => 'category'], 39)
            ->addPermission('admin.content.taxonomy_tag', 'roles.permissions.taxonomy_tag')
            ->addPermission('admin.content.taxonomy_tag.view', 'roles.permissions.taxonomy_tag_view', 'admin.taxonomies.index', ['type' => 'tag'], 40)
            ->addPermission('admin.content.taxonomy_tag.manage', 'roles.permissions.taxonomy_tag_manage', 'admin.taxonomies.index', ['type' => 'tag'], 41)
            ->addPermission('admin.content.menus', 'roles.permissions.taxonomy_menu')
            ->addPermission('admin.content.menus.view', 'roles.permissions.taxonomy_menu_view', 'admin.taxonomies.index', ['type' => 'menu'], 42)
            ->addPermission('admin.content.menus.manage', 'roles.permissions.taxonomy_menu_manage', 'admin.taxonomies.index', ['type' => 'menu'], 43)
            ->addPermission('admin.settings', 'roles.permissions.settings', 'admin.settings.index', order: 40)
            ->addPermission('admin.settings.general', 'roles.permissions.settings_general', 'admin.settings.edit', ['section' => 'general'], 41)
            ->addPermission('admin.settings.seo', 'roles.permissions.settings_seo', 'admin.settings.edit', ['section' => 'seo'], 42)
            ->addPermission('admin.settings.health', 'roles.permissions.settings_health', 'admin.settings.health', order: 43)
            ->addPermission('admin.settings.cache', 'roles.permissions.settings_cache', 'admin.settings.cache.edit', order: 44)
            ->addPermission('admin.settings.languages', 'roles.permissions.settings_languages', 'admin.settings.languages.index', order: 45)
            ->addPermission('admin.settings.mail', 'roles.permissions.settings_mail', 'admin.settings.mail.edit', order: 46);

        $permissions->addPermission('admin.settings.layouts', 'layouts.title');
        foreach (['view', 'manage', 'delete', 'restore'] as $operation) {
            $permissions->addPermission('admin.settings.layouts.'.$operation, 'layouts.permissions.'.$operation,
                $operation === 'view' ? 'admin.settings.layouts.index' : null, order: 47);
        }

        $permissions->addPermission('admin.settings.extensions', 'extensions.title');
        foreach (['view', 'upload', 'install', 'activate', 'deactivate', 'upgrade', 'uninstall', 'delete', 'plugins'] as $operation) {
            $permissions->addPermission('admin.settings.extensions.'.$operation, 'extensions.'.$operation, $operation === 'view' ? 'admin.settings.extensions.index' : null, order: 47);
        }

        foreach (PostTypeRegistry::values() as $type) {
            if (PostTypeRegistry::for($type)->hasMultipleKanban()) {
                $app->make(\App\Services\Posts\ContentBoards::class)->permission($type);
            }
            $app->make(PostWorkflowPermissions::class)
                ->for($type);
        }
    }
}
