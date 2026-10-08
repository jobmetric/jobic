<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Services\Posts\PostFlowProvisioner;
use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\Event;
use Illuminate\Contracts\Foundation\Application;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;

/** Register CMS-owned post types and their default taxonomy types. */
final class PostTypeDriver implements ApplicationDriver
{
    public function register(Application $app): void {}

    public function boot(Application $app): void
    {
        if (! TaxonomyTypeRegistry::has('category')) {
            TaxonomyTypeRegistry::register('category')->label('posts.category')->description('posts.category_description')->viewPermission('admin.posts.view')->managePermission('admin.posts.manage')->hierarchical()->url()->urlPrefix('category');
        }
        if (! TaxonomyTypeRegistry::has('tag')) {
            TaxonomyTypeRegistry::register('tag')->label('posts.tag')->description('posts.tag_description')->viewPermission('admin.posts.view')->managePermission('admin.posts.manage')->url()->urlPrefix('tag');
        }

        PostTypeRegistry::register('post')
            ->label('posts.types.post')->description('posts.types.post_description')
            ->option('view-permission', 'admin.posts.view')->option('manage-permission', 'admin.posts.manage')
            ->translationFields(['title', 'excerpt', 'content'])
            ->allowTaxonomy('category', 'categories', true)->allowTaxonomy('tag', 'tags', true)
            ->mediaCollection('base', false)->mediaCollection('gallery', true)->urlPrefix('blog')->comments()->workflow('post');

        PostTypeRegistry::register('page')
            ->label('posts.types.page')->description('posts.types.page_description')
            ->option('view-permission', 'admin.posts.view')->option('manage-permission', 'admin.posts.manage')
            ->translationFields(['title', 'excerpt', 'content'])->mediaCollection('base', false)->mediaCollection('gallery', true)
            ->urlPrefix('')->workflow('page');

        $app->booted(static function (): void {
            app(PostFlowProvisioner::class)->ensureDefaults();
        });
        Event::listen(MigrationsEnded::class, static function (): void {
            app(PostFlowProvisioner::class)->ensureDefaults();
        });

    }
}
