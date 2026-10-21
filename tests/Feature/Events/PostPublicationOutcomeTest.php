<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Services\PostLifecycle;
use Tests\TestCase;

class PostPublicationOutcomeTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_approval_publication_and_schedule_outcomes_are_minimal_and_success_only(): void
    {
        $this->actingAs($user = User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $lifecycle = app(PostLifecycle::class);
        $events = [];
        app('events')->listen(DomainEvent::class, function (DomainEvent $event) use (&$events): void {
            if (str_starts_with($event::key(), 'post.') && str_ends_with($event::key(), '.committed')) $events[] = [$event::key(), get_object_vars($event)];
        });
        DB::beginTransaction();
        $stream = $lifecycle->saveDraft($post, 'en', ['title' => 'secret draft content'], $user);
        $lifecycle->approve($post, 'en', (int) $stream->draft_revision_id, $user);
        self::assertSame([], $events);
        DB::commit();
        self::assertCount(2, $events);
        $lifecycle->saveDraft($post, 'en', ['title' => 'secret draft content'], $user);
        $lifecycle->approve($post, 'en', (int) $stream->draft_revision_id, $user);
        self::assertCount(2, $events);
        $schedule = $lifecycle->schedule($post, 'en', (int) $stream->draft_revision_id, now()->addHour(), $user);
        self::assertCount(3, $events);
        self::assertFalse($lifecycle->execute($schedule->id));
        self::assertCount(3, $events);
        $this->travel(2)->hours();
        self::assertTrue($lifecycle->execute($schedule->id));
        self::assertFalse($lifecycle->execute($schedule->id));
        self::assertCount(4, $events);
        DB::beginTransaction();
        $lifecycle->saveDraft($post, 'en', ['title' => 'rolled back content'], $user);
        DB::rollBack();
        self::assertCount(4, $events);
        self::assertStringNotContainsString('secret draft', json_encode($events));
        self::assertStringNotContainsString('rolled back', json_encode($events));
        self::assertStringNotContainsString($user->email, json_encode($events));
    }
    public function test_direct_status_changes_and_orphaned_schedule_cancellation_emit_once(): void
    {
        $this->actingAs($user = User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $statusEvents = [];
        $cancelEvents = [];
        app('events')->listen(\JobMetric\Post\Events\PostStatus\PostStatusUpdateCommittedEvent::class, function ($event) use (&$statusEvents): void { $statusEvents[] = $event->id; });
        app('events')->listen(\JobMetric\Post\Events\Schedule\ScheduleCancelCommittedEvent::class, function ($event) use (&$cancelEvents): void { $cancelEvents[] = $event->scheduleId; });
        $workflow = app(\JobMetric\Post\Services\PostWorkflow::class);
        $workflow->transition($post, 'disabled', $user);
        $workflow->transition($post, 'disabled', $user);
        self::assertSame([$post->id], $statusEvents);
        $lifecycle = app(PostLifecycle::class);
        $stream = $lifecycle->saveDraft($post, 'en', ['title' => 'future'], $user);
        $lifecycle->approve($post, 'en', (int) $stream->draft_revision_id, $user);
        $schedule = $lifecycle->schedule($post, 'en', (int) $stream->draft_revision_id, now()->addHour(), $user);
        $post->delete();
        self::assertFalse($lifecycle->execute($schedule->id));
        self::assertFalse($lifecycle->execute($schedule->id));
        self::assertSame([$schedule->id], $cancelEvents);
    }
}
