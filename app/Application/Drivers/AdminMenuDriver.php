<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\AdminMenu;
use App\Support\AdminMenuRegistry;
use App\Support\TaxonomyPermissions;
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
            ->permissions(['admin.content.posts.view', 'admin.content.posts.manage', 'admin.content.pages.view', 'admin.content.pages.manage'])
            ->childrenProvider(static function ($items): void {
                $postView = \JobMetric\Post\Facades\PostTypeRegistry::for('post')
                    ->getViewPermission() ?? 'admin.content.posts.view';
                $postManage = \JobMetric\Post\Facades\PostTypeRegistry::for('post')
                    ->getManagePermission() ?? 'admin.content.posts.manage';
                $postPermissions = array_values(array_unique([$postView, $postManage]));

                $items->item('all-posts')
                    ->label('posts.all_posts')
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

                    $taxonomyType = TaxonomyTypeRegistry::for($type);
                    $permissions = app(TaxonomyPermissions::class)->for($type, $taxonomyType);
                    $items->item($type)
                        ->label($label)
                        ->icon('list-tree')
                        ->route('admin.taxonomies.index', ['type' => $type])
                        ->permissions([$permissions['view'], $permissions['manage']])
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
                \JobMetric\Post\Facades\PostTypeRegistry::for('page')
                    ->getViewPermission() ?? 'admin.content.pages.view',
                \JobMetric\Post\Facades\PostTypeRegistry::for('page')
                    ->getManagePermission() ?? 'admin.content.pages.manage',
            ])
            ->active(static fn (): bool => request()->routeIs('admin.posts.*') && request()->route('type') === 'page')
            ->end()
            ->item('manage-menus')
            ->label('menus.manage')
            ->icon('menu')
            ->route('admin.taxonomies.index', ['type' => 'menu'])
            ->order(30)
            ->permissions(fn (): array => $this->taxonomyMenuPermissions('menu'))
            ->active(static fn (): bool => request()->routeIs('admin.taxonomies.*') && request()->route('type') === 'menu')
            ->end()
            ->item('menu-items')
            ->label('menus.items')
            ->icon('list-tree')
            ->order(40)
            ->permissions(fn (): array => $this->menuItemPermissions())
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
                        $builder = TaxonomyTypeRegistry::has($type) ? TaxonomyTypeRegistry::for($type) : null;
                        if (! $builder) {
                            return;
                        }
                        $permissions = app(TaxonomyPermissions::class)->for($type, $builder);
                        $items->item('menu-' . $taxonomy->id)
                            ->label(static fn (): string => TaxonomyPresentation::name($taxonomy))
                            ->icon('menu')
                            ->route('admin.taxonomies.index', ['type' => $type])
                            ->permissions([$permissions['view'], $permissions['manage']])
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
                        $permissions = app(TaxonomyPermissions::class)->for($type, $builder);
                        $items->item('taxonomy-' . $type)
                            ->label(static fn (): string => TaxonomyPresentation::typeLabel($type, $builder->getLabel()))
                            ->icon('list-tree')
                            ->route('admin.taxonomies.index', ['type' => $type])
                            ->permissions([$permissions['view'], $permissions['manage']])
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

    /** Return each registered menu-item taxonomy's independent access keys. */
    private function menuItemPermissions(): array
    {
        $permissions = [];
        foreach (TaxonomyTypeRegistry::values() as $type) {
            if (! str_starts_with($type, 'menu_')) {
                continue;
            }
            $builder = TaxonomyTypeRegistry::for($type);
            $typePermissions = app(TaxonomyPermissions::class)->for($type, $builder);
            $permissions[] = $typePermissions['view'];
            $permissions[] = $typePermissions['manage'];
        }

        return array_values(array_unique(array_filter($permissions)));
    }

    /** Return the independent access keys for one registered taxonomy type. */
    private function taxonomyMenuPermissions(string $type): array
    {
        if (! TaxonomyTypeRegistry::has($type)) {
            return [];
        }

        $permissions = app(TaxonomyPermissions::class)->for($type, TaxonomyTypeRegistry::for($type));

        return [$permissions['view'], $permissions['manage']];
    }
}
