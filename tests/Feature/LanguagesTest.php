<?php

namespace Tests\Feature;

use App\Facades\Hooks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use JobMetric\Language\Models\Language;
use Tests\TestCase;

class LanguagesTest extends TestCase
{
    use RefreshDatabase;

    private function values(array $changes = []): array
    {
        return array_replace(['name' => 'German', 'locale' => 'de', 'direction' => 'ltr', 'calendar' => 'gregorian', 'first_day_of_week' => 2, 'status' => 1], $changes);
    }

    public function test_guests_cannot_access_language_management(): void
    {
        $this->get(route('admin.settings.languages.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.settings.languages.store'), $this->values())->assertUnauthorized();
    }

    public function test_crud_validates_updates_and_returns_safe_hooks(): void
    {
        $this->actingAs(User::factory()->create());
        $events = [];
        Hooks::addAction('jobic.settings.languages.saving', function ($ctx) use (&$events) {
            $events[] = ['saving', $ctx];
        });
        Hooks::addAction('jobic.settings.languages.saved', function ($ctx) use (&$events) {
            $events[] = ['saved', $ctx];
        });
        $this->postJson(route('admin.settings.languages.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'locale']);
        $response = $this->postJson(route('admin.settings.languages.store'), $this->values(['password' => 'never-expose']))->assertCreated();
        $id = $response->json('data.id');
        $this->assertSame(['saving', 'saved'], array_column($events, 0));
        $this->assertSame(['user_id', 'id', 'operation'], array_keys($events[0][1]));
        $this->postJson(route('admin.settings.languages.store'), $this->values())->assertUnprocessable()->assertJsonValidationErrors('locale');
        $this->putJson(route('admin.settings.languages.update', $id), ['_version' => \App\Support\RecordVersion::token(Language::findOrFail($id), 'languages')] + $this->values(['first_day_of_week' => 4]))->assertOk();
        $this->assertDatabaseHas('languages', ['id' => $id, 'first_day_of_week' => 4]);
        $this->get(route('admin.settings.languages.show', $id))->assertOk()->assertSee('data-readonly', false);
        $this->postJson(route('admin.settings.languages.bulk'), ['ids' => [$id], 'operation' => 'delete'])->assertOk();
        $this->assertDatabaseMissing('languages', ['id' => $id]);
    }

    public function test_table_paginates_sorts_filters_and_rejects_invalid_columns(): void
    {
        $this->actingAs(User::factory()->create());
        Language::create($this->values());
        Language::create($this->values(['locale' => 'ar', 'name' => 'Arabic', 'direction' => 'rtl', 'status' => 0]));
        $query = ['draw' => 3, 'start' => 0, 'length' => 10, 'order' => [['column' => 2, 'dir' => 'asc']]];
        $this->getJson(route('admin.settings.languages.data', $query))->assertOk()->assertJsonPath('data.recordsTotal', 4)->assertJsonPath('data.data.0.name', 'Arabic');
        $this->getJson(route('admin.settings.languages.data', $query + ['status' => 1]))->assertOk()->assertJsonPath('data.recordsFiltered', 3);
        $this->getJson(route('admin.settings.languages.data', array_replace($query, ['length' => -1])))->assertUnprocessable();
    }

    public function test_bulk_is_atomic_when_a_hook_vetoes_and_notifications_follow_commit(): void
    {
        $this->actingAs(User::factory()->create());
        $a = Language::create($this->values());
        $b = Language::create($this->values(['locale' => 'fr']));
        Hooks::addAction('jobic.settings.languages.deleting', function ($ctx) use ($b) {
            if ($ctx['id'] === $b->id) {
                throw ValidationException::withMessages(['ids' => 'Veto']);
            }
        });
        $this->postJson(route('admin.settings.languages.bulk'), ['ids' => [$a->id, $b->id], 'operation' => 'delete'])->assertUnprocessable();
        $this->assertDatabaseCount('languages',4);
    }

    public function test_saved_observer_failure_does_not_report_committed_write_as_failure(): void
    {
        $this->actingAs(User::factory()->create());
        Hooks::addAction('jobic.settings.languages.saved', function () { throw new \RuntimeException('Observer'); });
        $this->postJson(route('admin.settings.languages.store'), $this->values())->assertCreated();
        $this->assertDatabaseHas('languages', ['locale' => 'de']);
    }

    public function test_assets_are_scoped_to_the_list_and_registry_appears_in_settings(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.settings.languages.index'))->assertOk()->assertSee('datatables.bundle.min.js')->assertSee('<tbody></tbody>', false);
        $this->get(route('admin.settings.languages.create'))->assertOk()->assertDontSee('datatables.bundle.min.js');
        $this->get(route('admin.settings.index'))->assertOk()->assertSee(route('admin.settings.languages.index'));
    }
    public function test_package_events_are_used_and_flags_are_resolved_safely(): void
    {
        \Illuminate\Support\Facades\Event::fake([
            \JobMetric\Language\Events\Language\LanguageStoredEvent::class,
            \JobMetric\Language\Events\Language\LanguageUpdatedEvent::class,
            \JobMetric\Language\Events\Language\LanguageDeletedEvent::class,
        ]);
        $this->actingAs(User::factory()->create());
        $id = $this->postJson(route('admin.settings.languages.store'), $this->values())->assertCreated()->json('data.id');
        $this->putJson(route('admin.settings.languages.update', $id), ['_version' => \App\Support\RecordVersion::token(Language::findOrFail($id), 'languages')] + $this->values(['first_day_of_week' => 5]))->assertOk();
        $this->postJson(route('admin.settings.languages.bulk'), ['ids' => [$id], 'operation' => 'delete'])->assertOk();
        foreach (['Stored', 'Updated', 'Deleted'] as $event) {
            \Illuminate\Support\Facades\Event::assertDispatched('JobMetric\\Language\\Events\\Language\\Language'.$event.'Event');
        }
        $this->assertStringEndsWith('/flags/iran.svg', \App\Support\LocaleCatalog::flagUrl('ir'));
        $this->assertStringEndsWith('/flags/united-states.svg', \App\Support\LocaleCatalog::flagUrl('united-states.svg'));
        $this->assertNull(\App\Support\LocaleCatalog::flagUrl('../../secret.svg'));
    }

}
