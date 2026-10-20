<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use Illuminate\Contracts\Foundation\Application;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;

/** Register CMS-owned post types and their default taxonomy types. */
final class PostTypeDriver implements ApplicationDriver
{
    /**
     * Register dependencies required by post type definitions.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
    }

    /**
     * Register built-in post types and their taxonomy capabilities.
     *
     * @param Application $app
     *
     * @return void
     */
    public function boot(Application $app): void
    {
        if (! TaxonomyTypeRegistry::has('category')) {
            TaxonomyTypeRegistry::register('category')
                ->label('posts.category')
                ->description('posts.category_description')
                ->permission('admin.content.taxonomy_category')
                ->hierarchical()
                ->url('category')
                ->hasSeo()
                ->filters(['status']);
        }
        if (! TaxonomyTypeRegistry::has('tag')) {
            TaxonomyTypeRegistry::register('tag')
                ->label('posts.tag')
                ->description('posts.tag_description')
                ->permission('admin.content.taxonomy_tag')
                ->url('tag')
                ->hasSeo()
                ->filters(['status']);
        }

        PostTypeRegistry::register('post')
            ->label('posts.types.post')
            ->description('posts.types.post_description')
            ->permission('admin.content.posts')
            ->translationFields([
                'title',
                'excerpt',
                'content',
            ])
            ->seo()
            ->metadataFields(['jobic_demo_key'])
            ->internalMetadata(['jobic_demo_key'])
            ->password()
            ->optimisticLocking()
            ->taxonomy('category', 'categories', true)
            ->taxonomy('tag', 'tags', true)
            ->filters([
                'status',
                'taxonomy.category',
                'taxonomy.tag',
            ])
            ->baseMedia()
            ->mediaCollection('gallery', true)
            ->changeStatusInList()
            ->optimisticLocking()
            ->help()
            ->url('blog')
            ->comments();

        PostTypeRegistry::register('page')
            ->label('posts.types.page')
            ->description('posts.types.page_description')
            ->permission('admin.content.pages')
            ->translationFields([
                'title',
                'excerpt',
                'content',
            ])
            ->seo()
            ->metadataFields(['jobic_demo_key'])
            ->internalMetadata(['jobic_demo_key'])
            ->password()
            ->baseMedia()
            ->changeStatusInList()
            ->optimisticLocking()
            ->help()
            ->url('')
            ->filters(['status']);

    }
}
