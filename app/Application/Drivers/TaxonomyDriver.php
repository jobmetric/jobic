<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\Hooks;
use App\Support\TaxonomyPresentation;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use JobMetric\Taxonomy\Facades\Taxonomy as TaxonomyService;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;

/**
 * Register the menu taxonomy and the dynamic item type for each menu.
 */
class TaxonomyDriver implements ApplicationDriver
{
    /**
     * Taxonomy owns its registry binding, so this driver has no bindings to add.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
    }

    /**
     * Register menu containers and their hierarchical item types.
     *
     * @param Application $app
     *
     * @return void
     */
    public function boot(Application $app): void
    {
        $app->booted(function (): void {
            Hooks::addFilter('jobic.taxonomies.help.items', function (mixed $items, array $context): mixed {
                $type = (string) ($context['type'] ?? '');

                return $type === 'menu' || str_starts_with($type, 'menu_') ? trans('menus.help_items') : $items;
            });
            Hooks::addFilter('jobic.taxonomies.help.intro', function (string $intro, array $context): string {
                $type = (string) ($context['type'] ?? '');

                return $type === 'menu' || str_starts_with($type, 'menu_') ? trans('menus.help_intro') : $intro;
            });

            TaxonomyTypeRegistry::register('menu')
                ->label('menus.title')
                ->description('menus.intro')
                ->permission('admin.content.menus')
                ->optimisticLocking()
                ->help()
                ->rowAction([
                    'key'   => 'items',
                    'label' => 'menus.configure',
                    'icon'  => 'list-tree',
                    'url'   => url('/admin/taxonomies/menu_:id'),
                ])
                ->changeStatusInList()
                ->filters(['status']);

            Hooks::addAction('jobic.taxonomies.persisted', function (array $context): void {
                if (($context['type'] ?? null) === 'menu' && ($context['record'] ?? null) instanceof Taxonomy) {
                    $this->registerMenuItemType($context['record']);
                }
            });

            Hooks::addAction('jobic.taxonomies.preparing', function (array $context): void {
                $type = (string) ($context['type'] ?? '');
                if (preg_match('/^menu_(\d+)$/', $type, $matches) !== 1) {
                    return;
                }

                $menu = Taxonomy::query()
                    ->with('translations')
                    ->where('type', 'menu')
                    ->find((int) $matches[1]);
                if ($menu) {
                    $this->registerMenuItemType($menu);
                }
            });

            Hooks::addAction('jobic.taxonomies.deleting-record', function (array $context): void {
                if (($context['type'] ?? null) !== 'menu') {
                    return;
                }

                Taxonomy::query()
                    ->where('type', 'menu_' . (int) $context['id'])
                    ->get()
                    ->each(function (Taxonomy $item): void {
                        if (Taxonomy::query()
                            ->whereKey($item->id)
                            ->exists()) {
                            TaxonomyService::delete($item->id);
                        }
                    });
            });

            Hooks::addAction('jobic.taxonomies.deleted', function (array $context): void {
                if (($context['type'] ?? null) === 'menu') {
                    TaxonomyTypeRegistry::unregister('menu_' . (int) $context['id']);
                }
            });

            $table = (string) config('taxonomy.tables.taxonomy', 'taxonomies');
            if (! Schema::hasTable($table)) {
                return;
            }

            Taxonomy::query()
                ->where('type', 'menu')
                ->get()
                ->each(fn (Taxonomy $menu) => $this->registerMenuItemType($menu));
        });
    }

    private function registerMenuItemType(Taxonomy $menu): void
    {
        $type = 'menu_' . $menu->id;
        $permission = 'admin.content.menu_item_' . $menu->id;
        app(\App\Support\Permissions\PermissionRegistry::class)
            ->addPermission($permission, 'roles.permissions.taxonomy_menu_item')
            ->addPermission($permission . '.view', 'roles.permissions.taxonomy_menu_item_view', 'admin.taxonomies.index', ['type' => $type])
            ->addPermission($permission . '.manage', 'roles.permissions.taxonomy_menu_item_manage', 'admin.taxonomies.index', ['type' => $type]);

        TaxonomyTypeRegistry::register($type)
            ->label(TaxonomyPresentation::name($menu))
            ->backUrl(url('/admin/taxonomies/menu'))
            ->permission($permission)
            ->hasSeo()
            ->optimisticLocking()
            ->help()
            ->hierarchical()
            ->baseMedia()
            ->changeStatusInList()
            ->filters(['status']);
    }
}
