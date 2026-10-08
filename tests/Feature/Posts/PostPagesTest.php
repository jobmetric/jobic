<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Facades\AdminMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_page_and_post_taxonomy_screens_render(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        foreach ([
            route('admin.posts.index', ['type' => 'post']),
            route('admin.posts.create', ['type' => 'post']),
            route('admin.posts.index', ['type' => 'page']),
            route('admin.posts.create', ['type' => 'page']),
            route('admin.taxonomies.index', ['type' => 'category']),
            route('admin.taxonomies.index', ['type' => 'tag']),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_content_menu_has_the_requested_post_and_menu_hierarchy(): void
    {
        app()->setLocale('fa');
        $this->actingAs(User::factory()->create());

        $items = AdminMenu::items('content');
        $this->assertSame(
            ['posts', 'pages', 'manage-menus'],
            array_column(array_slice($items, 0, 3), 'key')
        );
        $this->assertSame(
            ['all-posts', 'category', 'tag'],
            array_column($items[0]['children'], 'key')
        );
        $this->assertSame('همه نوشته‌ها', $items[0]['children'][0]['label']);
        $this->assertSame(route('admin.posts.index', ['type' => 'post']), $items[0]['children'][0]['url']);
    }
}
