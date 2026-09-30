<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;

/**
 * Register the menu taxonomy and the dynamic item type for each menu.
 */
class TaxonomyDriver implements ApplicationDriver
{
    /**
     * Taxonomy owns its registry binding, so this driver has no bindings to add.
     */
    public function register(Application $app): void {}

    /**
     * Register menu containers and their hierarchical item types.
     */
    public function boot(Application $app): void
    {
        $app->booted(function (): void {
            TaxonomyTypeRegistry::register('menu')
                ->label('menus.title')
                ->description('menus.intro')
                ->changeStatusInList();

            $table = (string) config('taxonomy.tables.taxonomy', 'taxonomies');
            if (! Schema::hasTable($table)) {
                return;
            }

            Taxonomy::query()->where('type', 'menu')->get()->each(function (Taxonomy $menu): void {
                TaxonomyTypeRegistry::register('menu_'.$menu->id)
                    ->label($menu->getTranslation('name') ?? __('menus.unnamed'))
                    ->hierarchical()
                    ->changeStatusInList();
            });
        });
    }
}
