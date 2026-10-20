<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostScheduledAction;
use JobMetric\Post\Services\PostLifecycle;
use Tests\TestCase;

/** Verify that scheduled publication uses the approved snapshot once. */
class PostScheduleRunnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_runner_publishes_due_approved_revision_once(): void
    {
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $lifecycle = app(PostLifecycle::class);
        $stream = $lifecycle->saveDraft($post, 'en', ['title' => 'Approved']);
        $lifecycle->approve($post, 'en', (int) $stream->draft_revision_id);
        $schedule = $lifecycle->schedule($post, 'en', (int) $stream->draft_revision_id, now()->addMinute());
        $lifecycle->saveDraft($post, 'en', ['title' => 'Later draft']);

        $this->artisan('posts:run-schedules')->assertSuccessful();
        $this->assertSame('pending', $schedule->refresh()->state);
        $this->travel(2)->minutes();
        $this->artisan('posts:run-schedules')->assertSuccessful();
        $this->artisan('posts:run-schedules')->assertSuccessful();

        $this->assertSame('completed', $schedule->refresh()->state);
        $this->assertSame('Approved', $lifecycle->published($post, 'en')['content']['title']);
        $this->assertSame(1, PostScheduledAction::where('state', 'completed')->count());
    }

    public function test_deleting_an_owner_cancels_its_pending_action(): void
    {
        $this->actingAs(User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $lifecycle = app(PostLifecycle::class);
        $stream = $lifecycle->saveDraft($post, 'en', ['title' => 'Future article']);
        $lifecycle->approve($post, 'en', (int) $stream->draft_revision_id);
        $schedule = $lifecycle->schedule($post, 'en', (int) $stream->draft_revision_id, now()->addHour());

        $this->deleteJson(route('admin.posts.destroy', ['type' => 'post', 'post' => $post->id]))->assertOk();
        $this->assertSame('cancelled', $schedule->refresh()->state);
        $this->travel(2)->hours();
        $this->artisan('posts:run-schedules')->assertSuccessful();
        $this->assertNull($stream->refresh()->live_revision_id);
    }
}
