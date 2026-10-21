<?php

declare(strict_types=1);

namespace Tests\Feature\Hooks;

use App\Facades\Hooks;
use App\Events\User\UserSavingEvent;
use App\Support\LifecycleEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use RuntimeException;
use Tests\TestCase;

class LifecycleEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_is_registered_translated_and_excluded_from_automation(): void
    {
        $registry = app('EventRegistry');
        $this->assertCount(26, LifecycleEvents::CLASSES);
        foreach (LifecycleEvents::CLASSES as $key => $class) {
            $this->assertSame($class, $registry->forKey($key));
            $definition = $class::definition();
            $this->assertContains('automation:disabled', $definition->tags);
            foreach (['en', 'fa'] as $locale) {
                foreach (['group', 'title', 'description'] as $field) {
                    $this->assertNotSame($definition->$field, __($definition->$field, [], $locale));
                }
            }
        }
    }

    public function test_domain_then_legacy_delivery_preserves_priority_and_original_context(): void
    {
        $calls = [];
        $context = ['id' => 12, 'data' => ['password' => 'secret'], 'record' => new \stdClass()];
        Event::listen(UserSavingEvent::class, function ($event) use (&$calls): void {
            $this->assertSame(['id' => 12], $event->context);
            $calls[] = 'domain';
        });
        Hooks::addAction('jobic.users.saving', function ($received) use (&$calls, $context): void {
            $this->assertSame($context, $received);
            $calls[] = 'legacy-first';
        }, 5);
        Hooks::addAction('jobic.users.saving', function () use (&$calls): void { $calls[] = 'legacy-last'; }, 20);
        Hooks::lifecycle('jobic.users.saving', $context);
        $this->assertSame(['domain', 'legacy-first', 'legacy-last'], $calls);
    }

    public function test_profile_command_exposes_typed_veto_before_persistence(): void
    {
        $user = User::factory()->create();
        Event::listen(\App\Events\Profile\ProfileSavingEvent::class, function ($event): void {
            $this->assertArrayNotHasKey('fields', $event->context);
            throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'Typed veto']);
        });
        $this->actingAs($user)->put(route('admin.account.profile.update'), [
            'name' => 'Rejected change', 'email' => 'rejected@example.test',
        ])->assertSessionHasErrors('name');
        $this->assertNotSame('Rejected change', $user->fresh()->name);
    }

    public function test_synchronous_listener_exception_rolls_back_and_stops_legacy_delivery(): void
    {
        $user = User::factory()->create();
        $legacyCalled = false;
        Hooks::addAction('jobic.users.saving', function () use (&$legacyCalled): void { $legacyCalled = true; });
        Event::listen(UserSavingEvent::class, function () use ($user): void {
            $user->update(['name' => 'Should roll back']);
            throw new RuntimeException('Veto');
        });
        try {
            DB::transaction(fn () => Hooks::lifecycle('jobic.users.saving', ['id' => $user->id]));
            $this->fail('Expected listener veto');
        } catch (RuntimeException $exception) {
            $this->assertSame('Veto', $exception->getMessage());
        }
        $this->assertFalse($legacyCalled);
        $this->assertNotSame('Should roll back', $user->fresh()->name);
    }
}
