<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\Kanban\KanbanCommentSavedEvent;
use App\Facades\Hooks;
use App\Support\KanbanOutcomeEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

/** Verify Kanban outcomes remain discoverable and compatible with legacy hooks. */
class KanbanDomainEventsTest extends TestCase
{
    public function test_committed_outcomes_are_registered_translated_and_automation_eligible(): void
    {
        $this->assertCount(11, KanbanOutcomeEvents::CLASSES);
        foreach (KanbanOutcomeEvents::CLASSES as $key => $class) {
            $this->assertSame($key, $class::key());
            $this->assertSame($class, app('EventRegistry')->forKey($key));
            $this->assertNotContains('automation:disabled', $class::definition()->tags);
            foreach (['en', 'fa'] as $locale) {
                foreach (['group', 'title', 'description'] as $field) {
                    $translation = $class::definition()->$field;
                    $this->assertNotSame($translation, trans($translation, [], $locale));
                }
            }
        }
    }

    public function test_outcome_dispatch_precedes_legacy_notification_and_excludes_secret_values(): void
    {
        $calls = [];
        Event::listen(KanbanCommentSavedEvent::class, function (KanbanCommentSavedEvent $event) use (&$calls): void {
            $this->assertSame(['type' => 'post', 'post_id' => 9, 'comment_id' => 12, 'user_id' => 3], $event->context);
            $calls[] = 'domain';
        });
        Hooks::addAction('jobic.posts.kanban.comment.saved', function (array $context) use (&$calls): void {
            $this->assertArrayHasKey('body', $context);
            $calls[] = 'legacy';
        });
        Hooks::kanbanOutcome('jobic.posts.kanban.comment.saved', [
            'type' => 'post', 'post_id' => 9, 'comment_id' => 12, 'user_id' => 3, 'body' => 'private',
        ]);
        $this->assertSame(['domain', 'legacy'], $calls);
    }

    public function test_rollback_discards_outcome_and_failed_domain_observer_does_not_hide_legacy_notification(): void
    {
        $calls = [];
        Event::listen(KanbanCommentSavedEvent::class, function () use (&$calls): void {
            $calls[] = 'domain';
            throw new RuntimeException('Observer failure');
        });
        Hooks::addAction('jobic.posts.kanban.comment.saved', function () use (&$calls): void { $calls[] = 'legacy'; });
        try {
            DB::transaction(function (): void {
                DB::afterCommit(static fn () => Hooks::kanbanOutcome('jobic.posts.kanban.comment.saved', ['post_id' => 9]));
                throw new RuntimeException('Rollback');
            });
        } catch (RuntimeException $exception) {
            $this->assertSame('Rollback', $exception->getMessage());
        }
        $this->assertSame([], $calls);
        Hooks::kanbanOutcome('jobic.posts.kanban.comment.saved', ['post_id' => 9]);
        $this->assertSame(['domain', 'legacy'], $calls);
    }
}
