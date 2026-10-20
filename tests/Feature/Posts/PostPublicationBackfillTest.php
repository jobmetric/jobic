<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Services\PostLifecycle;
use Tests\TestCase;

/** Keep historical content intact while introducing language streams. */
class PostPublicationBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_is_read_only_and_apply_creates_recoverable_backup(): void
    {
        Storage::fake('local');
        $post = Post::query()->create(['type' => 'post', 'status' => 'publish']);
        $post->translate('en', ['title' => 'Legacy live']);

        $this->artisan('posts:backfill-publication')->assertSuccessful();
        $this->assertSame(0, PostPublicationStream::count());

        $this->artisan('posts:backfill-publication', ['--apply' => true])->assertSuccessful();
        $this->assertSame('Legacy live', app(PostLifecycle::class)->published($post, 'en')['content']['title']);
        $this->assertSame('Legacy live', $post->getTranslation('title', 'en'));
        $files = Storage::disk('local')->allFiles('backups');
        $this->assertCount(2, $files);
        $this->assertTrue(collect($files)->contains(fn (string $path): bool => str_ends_with($path, '/manifest.json')));
    }

    public function test_backfill_creates_aggregate_release_without_replacing_a_legacy_language_revision(): void
    {
        Storage::fake('local');
        $post = Post::query()->create(['type' => 'post', 'status' => 'publish']);
        $post->translate('en', ['title' => 'Existing English']);
        $post->translate('fa', ['title' => 'Missing Persian']);
        $lifecycle = app(PostLifecycle::class);
        $english = $lifecycle->saveDraft($post, 'en', ['title' => 'Existing English']);
        $lifecycle->approve($post, 'en', (int) $english->draft_revision_id);
        $lifecycle->publish($post, 'en', (int) $english->draft_revision_id);
        $originalRevision = $english->refresh()->live_revision_id;

        $this->artisan('posts:backfill-publication', ['--apply' => true])->assertSuccessful();

        $this->assertSame($originalRevision, $english->refresh()->live_revision_id);
        $this->assertSame('Missing Persian', $lifecycle->published($post, 'fa')['content']['title']);
        $this->assertSame('Existing English', $lifecycle->published($post, 'en')['content']['title']);
        $this->assertCount(2, PostPublicationStream::query()->where('post_id', $post->id)->get());
    }
}
