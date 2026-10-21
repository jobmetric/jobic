<?php

declare(strict_types=1);

namespace Tests\Feature\Events;

use App\Events\Language\LanguageActivateEvent;
use App\Events\Language\LanguageDeactivateEvent;
use App\Events\Language\LanguageDeleteEvent;
use App\Events\Language\LanguageStoreEvent;
use App\Events\Language\LanguageUpdateEvent;
use App\Events\Role\RoleDeleteEvent;
use App\Events\Role\RoleStoreEvent;
use App\Events\Role\RoleUpdateEvent;
use App\Facades\Hooks;
use App\Models\User;
use App\Support\RecordVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use JobMetric\EventSystem\Contracts\DomainEvent;
use JobMetric\Language\Models\Language;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

class RegistryDomainEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_and_language_definitions_are_registered_and_translated(): void
    {
        foreach ([RoleStoreEvent::class, RoleUpdateEvent::class, RoleDeleteEvent::class, LanguageStoreEvent::class, LanguageUpdateEvent::class, LanguageDeleteEvent::class, LanguageActivateEvent::class, LanguageDeactivateEvent::class] as $class) {
            self::assertSame($class, app('EventRegistry')->forKey($class::key()));
            self::assertSame(['id' => 1, 'actorId' => 2], get_object_vars(new $class(1, 2)));
            foreach (['en', 'fa'] as $locale) {
                foreach (['group', 'title', 'description'] as $property) {
                    $key = $class::definition()->$property;
                    self::assertNotSame($key, trans($key, [], $locale));
                }
            }
        }
    }

    public function test_role_store_update_delete_skip_unchanged_rule_replacement(): void
    {
        $this->actingAs($actor = User::factory()->create());
        $events = [];
        app('events')->listen(DomainEvent::class, function (DomainEvent $event) use (&$events): void {
            if (str_starts_with($event::key(), 'jobic.role.')) {
                $events[] = $event::key();
            }
        });
        $values = ['name' => 'Editors', 'description' => '', 'parent_id' => null, 'ordering' => 10,
            'is_default' => 0, 'is_super' => 0, 'allow' => ['admin.users'], 'deny' => [],
            'rules' => [['driver' => 'time', 'payload' => ['from' => '08:00', 'to' => '18:00', 'timezone' => 'UTC']]]];
        $created = $this->postJson(route('admin.roles.store'), $values)->assertCreated();
        $id = (int) $created->json('data.id');
        $role = Role::findOrFail($id);
        $this->putJson(route('admin.roles.update', $id), $values + ['_version' => RecordVersion::token($role, 'roles')])->assertOk();
        self::assertSame(['jobic.role.stored'], $events);
        $this->putJson(route('admin.roles.update', $id), array_replace($values, ['name' => 'Changed', '_version' => RecordVersion::token($role->fresh(), 'roles')]))->assertOk();
        $this->postJson(route('admin.roles.bulk'), ['ids' => [$id], 'operation' => 'delete'])->assertOk();
        self::assertSame(['jobic.role.stored', 'jobic.role.updated', 'jobic.role.deleted'], $events);
    }

    public function test_language_mutations_suppress_noops_and_vetoed_batch_outcomes(): void
    {
        $this->actingAs(User::factory()->create());
        $events = [];
        app('events')->listen(DomainEvent::class, function (DomainEvent $event) use (&$events): void {
            if (str_starts_with($event::key(), 'jobic.language.')) {
                $events[] = $event::key();
            }
        });
        $values = ['name' => 'German', 'locale' => 'de', 'direction' => 'ltr', 'calendar' => 'gregorian', 'first_day_of_week' => 2, 'status' => 1];
        $created = $this->postJson(route('admin.settings.languages.store'), $values)->assertCreated();
        $id = (int) $created->json('data.id');
        $record = Language::findOrFail($id);
        $this->putJson(route('admin.settings.languages.update', $id), $values + ['_version' => RecordVersion::token($record, 'languages')])->assertOk();
        self::assertSame(['jobic.language.stored'], $events);
        $this->putJson(route('admin.settings.languages.update', $id), array_replace($values, ['name' => 'Changed', '_version' => RecordVersion::token($record->fresh(), 'languages')]))->assertOk();
        foreach (['deactivate', 'deactivate', 'activate'] as $operation) {
            $this->postJson(route('admin.settings.languages.bulk'), ['ids' => [$id], 'operation' => $operation])->assertOk();
        }
        self::assertSame(['jobic.language.stored', 'jobic.language.updated', 'jobic.language.deactivated', 'jobic.language.activated'], $events);
        $other = Language::create(array_replace($values, ['locale' => 'fr']));
        Hooks::addAction('jobic.settings.languages.deleting', function (array $context) use ($other): void {
            if ($context['id'] === $other->id) {
                throw ValidationException::withMessages(['ids' => 'Veto']);
            }
        });
        $this->postJson(route('admin.settings.languages.bulk'), ['ids' => [$id, $other->id], 'operation' => 'delete'])->assertUnprocessable();
        self::assertCount(4, $events);
        self::assertTrue(Language::whereKey($id)->exists());
        $this->postJson(route('admin.settings.languages.bulk'), ['ids' => [$id], 'operation' => 'delete'])->assertOk();
        self::assertSame('jobic.language.deleted', $events[4]);
    }
}
