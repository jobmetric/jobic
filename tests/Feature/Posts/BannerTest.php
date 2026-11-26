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
        $url = route('admin.posts.store', ['type' => 'banner']).'?locale=en';
        $payload = [
            'status' => 'publish',
            'translation' => ['en' => ['title' => 'Homepage banner']],
            'media_selection' => ['gallery' => json_encode([$id])],
        ];
        $this->postJson($url, $payload + [
            'media_item_metadata' => ['gallery' => [$id => ['title' => str_repeat('a', 256)]]],
        ])->assertUnprocessable()->assertJsonValidationErrors("media_item_metadata.gallery.$id.title");
        $result = $this->postJson($url, $payload + [
            'media_item_metadata' => ['gallery' => [$id => ['title' => 'Published caption', 'description' => 'Slide text']]],
        ])->assertOk();

        $banner = Post::findOrFail($result->json('data.id'));
        $this->assertSame('Published caption', $banner->getMetadata(GalleryItemForms::METADATA_KEY)['gallery'][$id]['title']);
        $this->app->setLocale('en');
        $this->assertStringContainsString('Published caption', app(JobicSlideshow::class)->handle(['banner_id' => $banner->id]) ?? '');
    }
}
