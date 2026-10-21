<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\User\UserActivateEvent;
use App\Events\User\UserDeactivateEvent;
use App\Events\User\UserDeleteEvent;
use App\Events\User\UserStoreEvent;
use App\Events\User\UserUpdateEvent;
use App\Models\User;
use App\Support\RecordVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use JobMetric\EventSystem\Contracts\DomainEvent;
use RuntimeException;
use Tests\TestCase;

class UserDomainEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_definitions_are_registered_translated_and_payloads_contain_only_identifiers(): void
    {
        $registry = app('EventRegistry');
        foreach ([UserStoreEvent::class, UserUpdateEvent::class, UserDeleteEvent::class, UserActivateEvent::class, UserDeactivateEvent::class] as $class) {
            self::assertSame($class, $registry->forKey($class::key()));
            self::assertSame($class::key(), $registry->definitionForKey($class::key())->key);
            foreach (['en', 'fa'] as $locale) {
                foreach (['group', 'title', 'description'] as $field) {
                    $key = $class::definition()->$field;
                    self::assertNotSame($key, trans($key, [], $locale));
                }
            }
            self::assertSame(['id' => 1, 'actorId' => 2], get_object_vars(new $class(1, 2)));
            self::assertTrue((new \ReflectionClass($class))->isReadOnly());
        }
    }

    public function test_create_waits_for_outer_commit_and_listener_failure_preserves_saved_response(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $received = [];
        app('events')->listen(UserStoreEvent::class, fn () => throw new RuntimeException('private payload'));
        app('events')->listen(UserStoreEvent::class, function (UserStoreEvent $event) use (&$received): void {
            $received[] = get_object_vars($event);
        });
        DB::beginTransaction();
        $response = $this->postJson(route('admin.users.store'), $this->values())->assertCreated();
        self::assertSame([], $received);
        DB::commit();
        self::assertSame([['id' => (int) $response->json('data.id'), 'actorId' => $actor->id]], $received);
    }

    public function test_rollback_invalid_input_and_unchanged_update_emit_no_outcomes(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $received = [];
        app('events')->listen(DomainEvent::class, function (DomainEvent $event) use (&$received): void {
            if (str_starts_with($event::key(), 'jobic.user.')) {
                $received[] = $event;
            }
        });
        $this->postJson(route('admin.users.store'), [])->assertUnprocessable();
        DB::beginTransaction();
        $this->postJson(route('admin.users.store'), $this->values())->assertCreated();
        DB::rollBack();
        self::assertSame([], $received);
        $record = User::factory()->create(['name' => 'Existing', 'email' => 'existing@example.test', 'status' => true]);
        $this->putJson(route('admin.users.update', $record->id), $this->values([
            '_version' => RecordVersion::token($record->fresh(), 'users'),
            'name' => $record->name, 'email' => $record->email, 'password' => '', 'password_confirmation' => '',
        ]))->assertOk();
        self::assertSame([], $received);
    }

    public function test_bulk_status_outcomes_are_success_only_and_skip_repeated_commands(): void
    {
        $this->actingAs(User::factory()->create());
        $record = User::factory()->create(['status' => true]);
        $received = [];
        app('events')->listen(DomainEvent::class, function (DomainEvent $event) use (&$received): void {
            if (str_starts_with($event::key(), 'jobic.user.')) {
                $received[] = $event::key();
            }
        });
        foreach (['activate', 'deactivate', 'deactivate', 'activate', 'delete'] as $operation) {
            $this->postJson(route('admin.users.bulk'), ['ids' => [$record->id], 'operation' => $operation])->assertOk();
        }
        self::assertSame(['jobic.user.deactivated', 'jobic.user.activated', 'jobic.user.deleted'], $received);
    }

    private function values(array $changes = []): array
    {
        return array_replace([
            'name' => 'Created', 'email' => 'created@example.test', 'status' => 1,
            'password' => 'strong-password-123', 'password_confirmation' => 'strong-password-123',
        ], $changes);
    }
}
