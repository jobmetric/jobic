<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Facades\Hooks;
use App\Models\User;
use App\Support\RecordVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Verify the complete administration user registry contract.
 */
class UsersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build valid user form values with optional replacements.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function values(array $changes = []): array
    {
        return array_replace([
            'name' => 'Second Admin',
            'email' => 'second@example.test',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
            'status' => 1,
        ], $changes);
    }

    /**
     * Ensure guests cannot reach user registry endpoints.
     */
    public function test_guests_cannot_access_user_management(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.users.store'), $this->values())->assertUnauthorized();
    }

    /**
     * Verify user creation, editing, safe hooks, password handling and history.
     */
    public function test_crud_persists_users_without_exposing_passwords(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $events = [];
        Hooks::addAction('jobic.users.saving', function (array $context) use (&$events): void {
            $events[] = ['saving', $context];
        });
        Hooks::addAction('jobic.users.saved', function (array $context) use (&$events): void {
            $events[] = ['saved', $context];
        });

        $this->postJson(route('admin.users.store'), [])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
        $response = $this->postJson(route('admin.users.store'), $this->values())->assertCreated();
        $id = (int) $response->json('data.id');
        $record = User::findOrFail($id);
        $this->assertTrue(Hash::check('strong-password-123', $record->password));
        $this->assertSame(['saving', 'saved'], array_column($events, 0));
        $this->assertSame(['user_id', 'id', 'operation'], array_keys($events[0][1]));
        $this->assertStringNotContainsString('strong-password-123', (string) \DB::table('record_changes')->where('resource', 'users')->value('changes'));

        $this->putJson(route('admin.users.update', $id), [
            '_version' => RecordVersion::token($record, 'users'),
            'name' => 'Renamed Admin', 'email' => 'renamed@example.test', 'password' => '', 'password_confirmation' => '', 'status' => 1,
        ])->assertOk();
        $this->assertDatabaseHas('users', ['id' => $id, 'name' => 'Renamed Admin', 'email_verified_at' => null]);
        $this->assertTrue(Hash::check('strong-password-123', $record->fresh()->password));
        $this->get(route('admin.users.show', $id))->assertOk()->assertSee('data-readonly', false)->assertDontSee($record->password, false);
    }

    /**
     * Verify table bounds, filtering and page-only DataTable assets.
     */
    public function test_table_is_server_side_bounded_and_assets_are_scoped(): void
    {
        $actor = User::factory()->create();
        User::factory()->create(['name' => 'Inactive', 'status' => false, 'email_verified_at' => null]);
        $this->actingAs($actor);
        $query = ['draw' => 1, 'start' => 0, 'length' => 10, 'order' => [['column' => 2, 'dir' => 'asc']]];

        $this->getJson(route('admin.users.data', $query + ['status' => 0]))->assertOk()->assertJsonPath('data.recordsFiltered', 1);
        $this->getJson(route('admin.users.data', array_replace($query, ['length' => -1])))->assertUnprocessable();
        $this->get(route('admin.users.index'))->assertOk()->assertSee('datatables.bundle.min.js')->assertSee('<tbody></tbody>', false);
        $this->get(route('admin.users.create'))->assertOk()->assertDontSee('datatables.bundle.min.js');
    }

    /**
     * Protect the acting user and retain atomicity when an extension vetoes a batch.
     */
    public function test_bulk_protects_current_user_and_is_atomic_on_veto(): void
    {
        $actor = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $this->actingAs($actor);

        $this->postJson(route('admin.users.bulk'), ['ids' => [$actor->id], 'operation' => 'delete'])
            ->assertUnprocessable()->assertJsonValidationErrors('ids');
        Hooks::addAction('jobic.users.deleting', function (array $context) use ($second): void {
            if ($context['id'] === $second->id) {
                throw ValidationException::withMessages(['ids' => 'Veto']);
            }
        });
        $this->postJson(route('admin.users.bulk'), ['ids' => [$first->id, $second->id], 'operation' => 'delete'])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $first->id]);
        $this->assertDatabaseHas('users', ['id' => $second->id]);
    }

    /**
     * Reject stale edits before lifecycle hooks execute.
     */
    public function test_stale_version_returns_conflict_without_running_hooks(): void
    {
        $actor = User::factory()->create();
        $record = User::factory()->create();
        $this->actingAs($actor);
        $called = false;
        Hooks::addAction('jobic.users.saving', function () use (&$called): void {
            $called = true;
        });

        $this->putJson(route('admin.users.update', $record), [
            '_version' => str_repeat('a', 64), 'name' => $record->name, 'email' => $record->email,
            'password' => '', 'password_confirmation' => '', 'status' => 1,
        ])->assertConflict();
        $this->assertFalse($called);
    }

    /**
     * Deny inactive accounts at sign-in and invalidate active administration sessions.
     */
    public function test_inactive_users_cannot_sign_in_or_continue_admin_sessions(): void
    {
        $user = User::factory()->create(['email' => 'inactive@example.test', 'password' => 'strong-password-123', 'status' => false]);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'strong-password-123'])
            ->assertSessionHasErrors('email');
        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
