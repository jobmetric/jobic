<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\User\UserStoreEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use JobMetric\EventSystem\Facades\EventBus;
use RuntimeException;
use Tests\TestCase;

class EventBusCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_dispatch_still_propagates_listener_failure(): void
    {
        app('events')->listen(UserStoreEvent::class, fn () => throw new RuntimeException('legacy behavior'));
        $this->expectException(RuntimeException::class);
        EventBus::dispatch(new UserStoreEvent(1, 2));
    }

    public function test_safe_bus_api_defers_and_isolates_the_same_listener_failure(): void
    {
        $received = [];
        app('events')->listen(UserStoreEvent::class, fn () => throw new RuntimeException('observer failure'));
        app('events')->listen(UserStoreEvent::class, function (UserStoreEvent $event) use (&$received): void {
            $received[] = $event->id;
        });
        DB::transaction(function () use (&$received): void {
            EventBus::dispatchAfterCommit(new UserStoreEvent(1, 2));
            self::assertSame([], $received);
        });
        self::assertSame([1], $received);
    }
    public function test_typed_registry_bus_and_facades_share_the_registered_definitions(): void
    {
        self::assertSame(app('EventRegistry'), app(\JobMetric\EventSystem\Support\EventRegistry::class));
        self::assertSame(app('EventBus'), app(\JobMetric\EventSystem\Support\EventBus::class));
        $received = [];
        app('events')->listen(UserStoreEvent::class, function (UserStoreEvent $event) use (&$received): void { $received[] = $event->id; });
        EventBus::dispatchByKey(UserStoreEvent::key(), 15, 2);
        self::assertSame([15], $received);
    }
}
