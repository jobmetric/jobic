<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use App\Facades\Hooks;
use Illuminate\Database\Seeder;

/** Show menu containers and nested menu items without fake public destinations. */
final class DemoMenusSeeder extends Seeder
{
    public function run(): void
    {
        $taxonomies = app(DemoTaxonomiesSeeder::class);
        $menu = $taxonomies->ensure('menu', 'main-menu', 'منوی نمونه', 'Sample menu');

        // The same registration hook is used after a menu is created in the admin UI.
        Hooks::action('jobic.taxonomies.persisted', ['type' => 'menu', 'record' => $menu]);

        $parent = $taxonomies->ensure('menu_'.$menu->id, 'menu-home', 'خانه', 'Home');
        $taxonomies->ensure('menu_'.$menu->id, 'menu-about', 'دربارهٔ ما', 'About us',
            parentId: (int) $parent->id);
    }
}
