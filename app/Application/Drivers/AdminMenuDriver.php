<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\AdminMenu;
use App\Support\AdminMenuRegistry;
use App\Support\TaxonomyPresentation;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;

/** Register the built-in admin navigation tree and live extension points. */
final class AdminMenuDriver implements ApplicationDriver
{
    /**
     * Register the built-in admin navigation tree and live extension points.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(AdminMenuRegistry::class);
    }

    /**
     * Boot the built-in admin navigation tree and live extension points.
     *
     * @param Application $app
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(Application $app): void
    {
        AdminMenu::register('dashboard')
            ->label('installation.dashboard')
            ->icon('activity')
            ->permission('admin.dashboard')
            ->active('admin.dashboard')
            ->sideblock('link')
            ->item('home')
            ->label('installation.dashboard')
            ->route('admin.dashboard')
            ->active('admin.dashboard')
            ->end();

        AdminMenu::register('users')
            ->label('users.title')
            ->icon('users')
            ->order(20)
            ->active(static fn (): bool => request()->routeIs('admin.users.*', 'admin.roles.*'))
            ->item('users')
            ->label('users.title')
            ->icon('users')
            ->route('admin.users.index')
            ->permissions(['admin.users.view', 'admin.users.manage'])
            ->active('admin.users.*')
            ->end()
            ->item('roles')
            ->label('roles.title')
            ->icon('shield')
            ->route('admin.roles.index')
            ->permissions(['admin.roles.view', 'admin.roles.manage'])
            ->active('admin.roles.*')
            ->end();

        AdminMenu::register('content')
            ->label('roles.permissions.content')
            ->icon('content')
            ->order(30)
            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*', 'admin.posts.*'))
            ->item('posts')
            ->label('posts.title')
            ->icon('file-text')
            ->order(10)
            ->permissions(['admin.posts.view', 'admin.posts.manage'])
            ->childrenProvider(static function ($items): void {
                $postView = (string) \JobMetric\Post\Facades\PostTypeRegistry::getOption('post', 'view-permission', 'admin.posts.view');
                $postManage = (string) \JobMetric\Post\Facades\PostTypeRegistry::getOption('post', 'manage-permission', 'admin.posts.manage');
                $postPermissions = array_values(array_unique([$postView, $postManage]));

                $items->item('all-posts')
                    ->label('posts.all')
                    ->icon('file-text')
                    ->route('admin.posts.index', ['type' => 'post'])
                    ->permissions($postPermissions)
                    ->order(10)
                    ->active(static fn (): bool => request()->routeIs('admin.posts.*') && request()->route('type') === 'post')
                    ->end();

                foreach (['category' => 'posts.category', 'tag' => 'posts.tag'] as $type => $label) {
                    if (! TaxonomyTypeRegistry::has($type)) {
                        continue;
                    }

                    $viewPermission = (string) TaxonomyTypeRegistry::getOption($type, 'view-permission', 'admin.posts.view');
                    $managePermission = (string) TaxonomyTypeRegistry::getOption($type, 'manage-permission', 'admin.posts.manage');
                    $items->item($type)
                        ->label($label)
                        ->icon('list-tree')
                        ->route('admin.taxonomies.index', ['type' => $type])
                        ->permissions(array_values(array_unique([$viewPermission, $managePermission])))
                        ->order($type === 'category' ? 20 : 30)
                        ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === $type)
                        ->end();
                }
            })
            ->end()
            ->item('pages')
            ->label('posts.types.page')
            ->icon('file-text')
            ->route('admin.posts.index', ['type' => 'page'])
            ->order(20)
            ->permissions([
                (string) \JobMetric\Post\Facades\PostTypeRegistry::getOption('page', 'view-permission', 'admin.posts.view'),
                (string) \JobMetric\Post\Facades\PostTypeRegistry::getOption('page', 'manage-permission', 'admin.posts.manage'),
            ])
            ->active(static fn (): bool => request()->routeIs('admin.posts.*') && request()->route('type') === 'page')
            ->end()
            ->item('manage-menus')
            ->label('menus.manage')
            ->icon('menu')
            ->route('admin.taxonomies.index', ['type' => 'menu'])
            ->order(30)
            ->permissions(['admin.menus.view', 'admin.menus.manage'])
            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === 'menu')
            ->end()
            ->item('menu-items')
            ->label('menus.items')
            ->icon('list-tree')
            ->order(40)
            ->permissions(['admin.menus.view', 'admin.menus.manage'])
            ->childrenProvider(static function ($items): void {
                $table = (string) config('taxonomy.tables.taxonomy', 'taxonomies');
                if (! Schema::hasTable($table)) {
                    return;
                }

                Taxonomy::query()
                    ->with('translations')
                    ->where('type', 'menu')
                    ->orderBy('id')
                    ->get()
                    ->each(static function (Taxonomy $taxonomy) use ($items): void {
                        $type = 'menu_' . $taxonomy->id;
                        $items->item('menu-' . $taxonomy->id)
                            ->label(static fn (): string => TaxonomyPresentation::name($taxonomy))
                            ->icon('menu')
                            ->route('admin.taxonomies.index', ['type' => $type])
                            ->order(50)
                            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === $type)
                            ->end();
                    });
            })
            ->end()
            ->provider(static function ($items): void {
                collect(TaxonomyTypeRegistry::values())
                    ->reject(static fn (string $type): bool => in_array($type, ['menu', 'category', 'tag'], true) || str_starts_with($type, 'menu_'))
                    ->each(static function (string $type) use ($items): void {
                        $builder = TaxonomyTypeRegistry::for($type);
                        $permission = (string) TaxonomyTypeRegistry::getOption($type, 'view-permission', 'admin.content');
                        $managePermission = (string) TaxonomyTypeRegistry::getOption($type, 'manage-permission', 'admin.content');
                        $items->item('taxonomy-' . $type)
                            ->label(static fn (): string => TaxonomyPresentation::typeLabel($type, $builder->getLabel()))
                            ->icon('list-tree')
                            ->route('admin.taxonomies.index', ['type' => $type])
                            ->permissions(array_values(array_unique([$permission, $managePermission])))
                            ->order(60)
                            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === $type)
                            ->end();
                    });
            });

        AdminMenu::register('settings')
            ->label('installation.settings')
            ->icon('settings')
            ->order(40)
            ->active('admin.settings.*')
            ->provider(static function ($items): void {
                $settings = (array) view()->shared('settingsSections', []);
                foreach ($settings as $key => $item) {
                    $itemUrl = (string) ($item['url'] ?? route('admin.settings.index'));
                    $items->item((string) $key)
                        ->label((string) ($item['label'] ?? trans('admin.sections.' . $key)))
                        ->icon((string) ($item['icon'] ?? 'settings'))
                        ->url($itemUrl)
                        ->active(static fn (): bool => url()->current() === rtrim($itemUrl, '/'))
                        ->order((int) ($item['order'] ?? 0))
                        ->end();
                }

                if ($settings !== []) {
                    $items->item('all-settings')
                        ->label('admin.all_settings')
                        ->route('admin.settings.index')
                        ->active('admin.settings.index')
                        ->order(-100)
                        ->end();
                }
            });
    }
}
