<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Facades\AdminMenu;
use App\Support\AdminMenuRegistry;
use InvalidArgumentException;
use Tests\TestCase;

class AdminMenuRegistryTest extends TestCase
{
    public function test_it_resolves_a_three_level_menu_and_renders_a_section(): void
    {
        $registry = app(AdminMenuRegistry::class);
        $registry->register('extension')->label('Extension')->icon('apps')
            ->item('catalog')->label('Catalog')
            ->child('categories')->label('Categories')->url('/admin/catalog/categories')->end()->end();

        $sections = $registry->sections();

        $this->assertSame('extension', $sections[0]['key']);
        $this->assertSame('Catalog', $sections[0]['items'][0]['label']);
        $this->assertSame('Categories', $sections[0]['items'][0]['children'][0]['label']);
        $this->assertStringContainsString('Categories', $registry->render('extension')->toHtml());
        $this->assertStringContainsString('data-sidebar-panel-target="extension"', $registry->render()->toHtml());
    }

    public function test_it_rejects_a_fourth_navigation_level(): void
    {
        $registry = app(AdminMenuRegistry::class);
        $registry->register('extension')->label('Extension')->icon('apps');

        $this->expectException(InvalidArgumentException::class);
        $registry->register('extension')->label('Extension')
            ->item('catalog')->label('Catalog')->child('categories')->label('Categories')
            ->child('leaf')->label('Too deep')->end()->end()->end();
    }

    public function test_it_can_add_items_after_the_section_is_registered(): void
    {
        $registry = app(AdminMenuRegistry::class);
        $registry->register('extension')->label('Extension')
            ->item('catalog')->label('Catalog')->child('products')->label('Products')->url('/admin/catalog/products')->end()->end();

        $this->assertSame('Products', $registry->items('extension')[0]['children'][0]['label']);
    }

    public function test_every_rendered_navigation_item_has_an_icon(): void
    {
        $registry = app(AdminMenuRegistry::class);
        $registry->register('icon-test')->label('Icon test')->icon('apps')
            ->item('parent')->label('Parent')->icon('file-text')
            ->child('child')->label('Child')->end()->end();
        $items = $registry->items('icon-test');
        $assertIcons = function (array $entries) use (&$assertIcons): void {
            foreach ($entries as $entry) {
                $this->assertNotSame('', $entry['icon'] ?? '');
                $assertIcons($entry['children'] ?? []);
            }
        };

        $assertIcons($items);

        $html = $registry->render('icon-test')->toHtml();
        $this->assertStringContainsString('subsidebar-menu__item-icon', $html);
        $this->assertStringContainsString('icon-file-text', $html);
    }

    public function test_dynamic_items_are_defined_with_builders(): void
    {
        $registry = app(AdminMenuRegistry::class);
        $registry->register('extension')->label('Extension')->provider(static function ($items): void {
            $items->item('dynamic')->label('Dynamic')->route('admin.dashboard')->end();
        });

        $this->assertSame('Dynamic', $registry->items('extension')[0]['label']);
    }

    public function test_an_external_provider_can_register_a_section_through_the_facade(): void
    {
        AdminMenu::register('external-package')
            ->label('External package')
            ->icon('apps')
            ->item('overview')
            ->label('Package overview')
            ->url('/admin/package')
            ->end();

        $registry = app(AdminMenuRegistry::class);

        $this->assertSame('External package', $registry->sections()[0]['label']);
        $this->assertSame('Package overview', $registry->items('external-package')[0]['label']);
    }
}
