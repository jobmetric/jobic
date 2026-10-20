<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\Editor\EditorDocuments;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\Demo\DemoMediaSeeder;
use Database\Seeders\Demo\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use JobMetric\Media\FileManager;
use JobMetric\Media\Models\Media;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostWorkflowDefinition;
use JobMetric\Rolix\Models\Role;
use JobMetric\Setting\Models\Setting;
use JobMetric\Taxonomy\Models\Taxonomy;
use Tests\TestCase;

/** Verify that an opt-in CMS tour is complete and safe to run repeatedly. */
class DemoSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seeder_creates_only_bootstrap_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, Setting::query()->count());
        $this->assertSame(0, Post::query()->count());
        $this->assertSame(0, Taxonomy::query()->count());
        $this->assertSame(0, Media::query()->count());
        $this->assertTrue(User::query()->where('email', 'admin@jobic.test')->exists());
    }

    public function test_demo_seeders_create_bilingual_content_without_overwriting_existing_settings(): void
    {
        Storage::fake('public');
        Setting::query()->create(['form' => 'jobic_seo', 'key' => 'jobic_seo_meta_title',
            'value' => 'My real title', 'is_json' => false]);

        $this->seed(DemoSeeder::class);

        $admin = User::query()->where('email', 'admin@jobic.test')->firstOrFail();
        $this->assertTrue($admin->memberships()->whereHas('role', fn ($query) => $query->where('is_super', true))->exists());
        $this->assertSame(1, Role::query()->where('name', 'Jobic demo content editor')->count());
        $this->assertSame(2, Taxonomy::query()->where('type', 'category')->count());
        $this->assertSame(2, Taxonomy::query()->where('type', 'tag')->count());
        $this->assertSame(1, Taxonomy::query()->where('type', 'menu')->count());
        $this->assertSame(2, Taxonomy::query()->where('type', 'like', 'menu_%')->count());
        $this->assertSame(1, Post::query()->where('type', 'post')->count());
        $this->assertSame(1, Post::query()->where('type', 'page')->count());
        $this->assertSame(1, PostWorkflowDefinition::query()->where('state', 'draft')->count());
        $this->assertNull(PostWorkflowDefinition::query()->where('state', 'active')->first());
        $this->assertSame('My real title', Setting::query()->where('key', 'jobic_seo_meta_title')->value('value'));

        $image = DemoMediaSeeder::image();
        $this->assertInstanceOf(Media::class, $image);
        $this->assertTrue(Storage::disk($image->disk)->exists(app(FileManager::class)->path($image)));

        $post = Post::query()->where('type', 'post')->firstOrFail();
        $this->assertSame('به Jobic خوش آمدید', $post->getTranslation('title', 'fa'));
        $this->assertSame('Welcome to Jobic', $post->getTranslation('title', 'en'));
        $this->assertNotNull(app(EditorDocuments::class)->read($post, 'fa'));
        $this->assertNotNull(app(EditorDocuments::class)->read($post, 'en'));
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $post->id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'Welcome to Jobic');

        $this->seed(DemoSeeder::class);

        $this->assertSame(2, Post::query()->count());
        $this->assertSame(1, PostWorkflowDefinition::query()->count());
        $this->assertSame(7, Taxonomy::query()->count());
        $this->assertSame(1, Media::query()->where('type', 'f')->count());
        $this->assertSame(1, Role::query()->where('name', 'Jobic demo content editor')->count());
        $this->assertSame('My real title', Setting::query()->where('key', 'jobic_seo_meta_title')->value('value'));
    }
}
