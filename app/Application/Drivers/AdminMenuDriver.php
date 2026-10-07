<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
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
        $menu = $app->make(AdminMenuRegistry::class);

        $menu->register('dashboard')
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

        $menu->register('users')
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

        $menu->register('content')
            ->label('roles.permissions.content')
            ->icon('content')
            ->order(30)
            ->active('admin.taxonomies.*')
            ->item('manage-menus')
            ->label('menus.manage')
            ->icon('menu')
            ->route('admin.taxonomies.index', ['type' => 'menu'])
            ->permissions(['admin.menus.view', 'admin.menus.manage'])
            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === 'menu')
            ->end()
            ->item('menu-items')
            ->label('menus.items')
            ->icon('list-tree')
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
                            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === $type)
                            ->end();
                    });
            })
            ->end()
            ->provider(static function ($items): void {
                collect(TaxonomyTypeRegistry::values())
                    ->reject(static fn (string $type): bool => $type === 'menu' || str_starts_with($type, 'menu_'))
                    ->each(static function (string $type) use ($items): void {
                        $builder = TaxonomyTypeRegistry::for($type);
                        $permission = (string) TaxonomyTypeRegistry::getOption($type, 'view-permission', 'admin.content');
                        $managePermission = (string) TaxonomyTypeRegistry::getOption($type, 'manage-permission', 'admin.content');
                        $items->item('taxonomy-' . $type)
                            ->label(static fn (): string => TaxonomyPresentation::typeLabel($type, $builder->getLabel()))
                            ->icon('list-tree')
                            ->route('admin.taxonomies.index', ['type' => $type])
                            ->permissions(array_values(array_unique([$permission, $managePermission])))
                            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === $type)
                            ->end();
                    });
            });

        $menu->register('settings')
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
