<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Extensions\Module\JobicSlideshow\JobicSlideshow;
use App\Models\User;
use App\Support\GalleryItemForms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use JobMetric\Post\Models\Post;
use Tests\TestCase;

class BannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_slideshow_instance_saves_and_reopens_the_selected_banner(): void
    {
        $this->withoutVite()->actingAs(User::factory()->create());
        $previous = Post::query()->create(['type' => 'banner', 'status' => 'draft']);
        $banner = Post::query()->create(['type' => 'banner', 'status' => 'draft']);
        $extension = \JobMetric\Extension\Models\Extension::query()->create([
            'extension' => 'Module', 'name' => 'JobicSlideshow',
            'namespace' => JobicSlideshow::class, 'info' => ['multiple' => JobicSlideshow::multiple()],
        ]);
        $plugin = $extension->plugins()->create([
            'name' => 'Slideshow', 'status' => true, 'fields' => ['banner_id' => (string) $previous->id],
        ]);
        $service = app(\App\Services\Extensions\Extensions::class);
        $this->post(route('admin.settings.extensions.plugins.update', [$extension->id, $plugin->id]), [
            'name' => $plugin->name,
            'status' => '1',
            '_version' => $service->pluginVersion($plugin->fresh()),
            'fields' => ['banner_id' => (string) $banner->id],
        ], ['Accept' => 'application/json', 'X-Requested-With' => 'XMLHttpRequest'])->assertOk();
        $this->assertSame((string) $banner->id, (string) $plugin->fresh()->fields['banner_id']);
        $this->get(route('admin.settings.extensions.plugins.edit', [$extension->id, $plugin->id]))
            ->assertOk()->assertSee('value="'.$banner->id.'" selected', false);

        $this->postJson(route('admin.settings.extensions.plugins.store', [$extension->id]), [
            'name' => 'Second slideshow',
            'status' => '1',
            'fields' => ['banner_id' => (string) $previous->id, 'carousel_dots' => '1', 'carousel_autoplay' => '5'],
        ])->assertOk();
        $this->assertSame(2, $extension->plugins()->count());
        $this->assertSame('5', (string) $extension->plugins()->latest('id')->firstOrFail()->fields['carousel_autoplay']);
        $this->assertSame((string) $banner->id, (string) $plugin->fresh()->fields['banner_id']);
    }

    public function test_list_image_column_requires_a_featured_image_collection(): void
    {
        $this->withoutVite()->actingAs(User::factory()->create());
        $this->get(route('admin.posts.index', ['type' => 'banner']))
            ->assertOk()->assertDontSee('data-column="media"', false);
        $this->get(route('admin.posts.index', ['type' => 'post']))
            ->assertOk()->assertSee('data-column="media"', false);
    }

    public function test_banner_status_allowlist_applies_to_options_and_save_validation(): void
    {
        $workflow = app(\App\Services\Posts\PostWorkflow::class);
        $banner = new Post(['type' => 'banner']);
        $this->assertSame(['draft', 'publish', 'disabled'], array_keys($workflow->allStatusOptions($banner, 'en')));
        $this->assertContains('archive', $workflow->availableStatuses(new Post(['type' => 'page'])));

        $this->actingAs(User::factory()->create())->postJson(
            route('admin.posts.store', ['type' => 'banner']).'?locale=en',
            ['status' => 'pending', 'translation' => ['en' => ['title' => 'Banner']]],
        )->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_banner_status_is_changed_directly_and_workflow_routes_are_disabled(): void
    {
        $this->withoutVite()->actingAs(User::factory()->create());
        $this->get(route('admin.posts.workflow.index', ['type' => 'banner']))->assertNotFound();
        $this->postJson(route('admin.posts.workflow.save', ['type' => 'banner']))->assertNotFound();

        $banner = Post::query()->create(['type' => 'banner', 'status' => 'draft']);
        $this->postJson(route('admin.posts.bulk', ['type' => 'banner']), [
            'operation' => 'deactivate',
            'ids' => [$banner->id],
        ])->assertOk();

        $this->assertSame('disabled', $banner->fresh()->status);
        $this->postJson(route('admin.posts.lifecycle.command', ['type' => 'banner', 'post' => $banner->id]))
            ->assertNotFound();
    }

    public function test_banner_captions_are_validated_and_rendered_from_the_published_banner(): void
    {
        $this->withoutVite()->actingAs(User::factory()->create());
        $this->get(route('admin.posts.create', ['type' => 'banner']))
            ->assertOk()
            ->assertSee('data-media-item-dialog', false)
            ->assertDontSee('media_selection[base]', false);
        $this->get(route('admin.posts.create', ['type' => 'post']))
            ->assertOk()->assertDontSee('data-media-item-dialog', false);

        $id = $this->postJson(route('admin.media-manager.upload'), [
            'file' => UploadedFile::fake()->image('banner-slide.png'),
        ])->assertCreated()->json('data.id');
        $secondId = $this->postJson(route('admin.media-manager.upload'), [
            'file' => UploadedFile::fake()->image('second-banner-slide.png', 120, 80),
        ])->assertCreated()->json('data.id');
        $url = route('admin.posts.store', ['type' => 'banner']).'?locale=en';
        $payload = [
            'status' => 'draft',
            'translation' => ['en' => ['title' => 'Homepage banner']],
            'media_selection' => ['gallery' => json_encode([$id, $secondId])],
        ];
        $this->postJson($url, $payload + [
            'media_item_metadata' => ['gallery' => [$id => ['title' => str_repeat('a', 256)]]],
        ])->assertUnprocessable()->assertJsonValidationErrors("media_item_metadata.gallery.$id.title");
        $result = $this->postJson($url, $payload + [
            'media_item_metadata' => ['gallery' => [$id => ['title' => 'Published caption', 'description' => 'Slide text']]],
        ])->assertOk();

        $banner = Post::findOrFail($result->json('data.id'));
        $this->assertSame('Published caption', $banner->getMetadata(GalleryItemForms::METADATA_KEY)['gallery'][$id]['title']);
        $this->assertNull(app(JobicSlideshow::class)->handle(['banner_id' => $banner->id]));
        $this->postJson(route('admin.posts.bulk', ['type' => 'banner']), [
            'operation' => 'activate',
            'ids' => [$banner->id],
        ])->assertOk();
        $this->app->setLocale('en');
        $html = app(JobicSlideshow::class)->handle(['banner_id' => $banner->id]) ?? '';
        $this->assertStringContainsString('Published caption', $html);
        $this->assertStringContainsString('data-carousel-next', $html);
        $this->assertSame(2, substr_count($html, '<img '));
        $custom = app(JobicSlideshow::class)->handle([
            'banner_id' => $banner->id, 'carousel_loop' => '1', 'carousel_arrows' => '0',
            'carousel_dots' => '1', 'carousel_captions' => '0', 'carousel_autoplay' => '5',
        ]) ?? '';
        $this->assertStringContainsString('data-loop="1" data-interval="5"', $custom);
        $this->assertSame(2, substr_count($custom, 'data-carousel-dot='));
        $this->assertStringNotContainsString('data-carousel-next', $custom);
        $this->assertStringNotContainsString('<figcaption>', $custom);
        $this->app->setLocale('fa');
        $this->assertStringContainsString('Published caption', app(JobicSlideshow::class)->handle(['banner_id' => $banner->id]) ?? '');
    }
}
