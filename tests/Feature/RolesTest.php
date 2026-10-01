<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Facades\Hooks;
use App\Models\User;
use App\Support\RecordVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

/**
 * Verify system-role CRUD, hierarchy, permissions, rules, and memberships.
 */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Build valid system-role form values with optional replacements.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function values(array $changes = []): array
    {
        return array_replace([
            'name' => 'Content manager',
            'description' => 'Manages content and users.',
            'parent_id' => null,
            'ordering' => 10,
            'is_default' => 0,
            'is_super' => 0,
            'allow' => ['admin', 'admin.users'],
            'deny' => ['admin.settings.general'],
            'rules' => [['driver' => 'time', 'payload' => ['from' => '08:00', 'to' => '18:00', 'timezone' => 'UTC']]],
        ], $changes);
    }

    /**
     * Ensure guests cannot reach role registry endpoints.
     */
    public function test_guests_cannot_access_role_management(): void
    {
        $this->get(route('admin.roles.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.roles.store'), $this->values())->assertUnauthorized();
    }

    /**
     * Persist role hierarchy, permissions, rules, history, and safe hooks.
     */
    public function test_crud_delegates_system_role_persistence_to_rolix(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $events = [];
        Hooks::addAction('jobic.roles.saving', function (array $context) use (&$events): void {
            $events[] = ['saving', $context];
        });
        Hooks::addAction('jobic.roles.saved', function (array $context) use (&$events): void {
            $events[] = ['saved', $context];
        });

        $response = $this->postJson(route('admin.roles.store'), $this->values())->assertCreated();
        $role = Role::with('rules')->findOrFail((int) $response->json('data.id'));
        $this->assertSame('system', $role->type);
        $this->assertSame(['admin', 'admin.users'], $role->allow);
        $this->assertSame('time', $role->rules->first()->driver);
        $this->assertSame(['saving', 'saved'], array_column($events, 0));
        $this->assertSame(['user_id', 'id', 'operation'], array_keys($events[0][1]));
        $this->assertDatabaseHas('role_paths', ['role_id' => $role->id, 'path_id' => $role->id, 'level' => 0]);
        $this->assertDatabaseHas('record_changes', ['resource' => 'roles', 'record_id' => $role->id]);

        $this->putJson(route('admin.roles.update', $role), $this->values([
            '_version' => RecordVersion::token($role, 'roles'),
            'name' => 'Senior content manager',
        ]))->assertOk();
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => 'Senior content manager', 'type' => 'system']);
    }

    /**
     * Validate bounded tables, page-only assets, and form schema presentation.
     */
    public function test_table_and_form_follow_the_registry_contract(): void
    {
        $this->actingAs(User::factory()->create());
        Role::query()->create(['type' => 'system', 'name' => 'Default', 'is_default' => true, 'is_super' => false, 'ordering' => 1]);
        $query = ['draw' => 1, 'start' => 0, 'length' => 10, 'order' => [['column' => 1, 'dir' => 'asc']]];

        $this->getJson(route('admin.roles.data', $query + ['kind' => 'default']))->assertOk()->assertJsonPath('data.recordsFiltered', 1);
        $this->getJson(route('admin.roles.data', array_replace($query, ['length' => -1])))->assertUnprocessable();
        $this->get(route('admin.roles.index'))->assertOk()->assertSee('datatables.bundle.min.js')->assertSee('<tbody></tbody>', false);
        $this->get(route('admin.roles.create'))->assertOk()->assertDontSee('datatables.bundle.min.js')
            ->assertSee('data-permission-tree="allow"', false)->assertSee('data-permission-tree="deny"', false)
            ->assertSee('roleForm', false)->assertSee('ip_range', false)->assertDontSee('custom_expression', false);
    }

    /**
     * Display the full hierarchy while searching only the current role name.
     */
    public function test_hierarchical_role_search_does_not_match_ancestor_names(): void
    {
        $this->actingAs(User::factory()->create());
        $parent = $this->postJson(route('admin.roles.store'), $this->values(['name' => 'Parent']))->assertCreated();
        $this->postJson(route('admin.roles.store'), $this->values([
            'name' => 'Child',
            'parent_id' => (int) $parent->json('data.id'),
        ]))->assertCreated();
        $query = ['draw' => 1, 'start' => 0, 'length' => 10, 'order' => [['column' => 1, 'dir' => 'asc']]];

        $parentRows = $this->getJson(route('admin.roles.data', $query + ['search' => ['value' => 'Parent']]))
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->json('data.data');
        $this->assertSame('Parent', array_values($parentRows)[0]['path_label']);

        $childRows = $this->getJson(route('admin.roles.data', $query + ['search' => ['value' => 'Child']]))
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->json('data.data');
        $this->assertSame('Parent > Child', array_values($childRows)[0]['path_label']);
    }

    /**
     * Assign system roles to users and reject stale role edits before hooks.
     */
    public function test_user_memberships_and_optimistic_conflicts_are_enforced(): void
    {
        $actor = User::factory()->create();
        $user = User::factory()->create();
        $role = Role::query()->create(['type' => 'system', 'name' => 'Editor', 'is_default' => false, 'is_super' => false, 'ordering' => 1]);
        $this->actingAs($actor);

        $this->putJson(route('admin.users.update', $user), [
            '_version' => RecordVersion::token($user->fresh(), 'users'), 'name' => $user->name, 'email' => $user->email,
            'password' => '', 'password_confirmation' => '', 'status' => 1, 'role_ids' => [$role->id],
        ])->assertOk();
        $this->assertTrue($user->fresh()->hasRole($role));

        $called = false;
        Hooks::addAction('jobic.roles.saving', function () use (&$called): void {
            $called = true;
        });
        $this->putJson(route('admin.roles.update', $role), $this->values(['_version' => str_repeat('a', 64)]))->assertConflict();
        $this->assertFalse($called);
    }

    /**
     * Keep bulk deletion atomic when a role hook vetoes one selected record.
     */
    public function test_bulk_delete_is_atomic_and_super_roles_are_protected(): void
    {
        $this->actingAs(User::factory()->create());
        $first = Role::query()->create(['type' => 'system', 'name' => 'First', 'is_default' => false, 'is_super' => false, 'ordering' => 1]);
        $second = Role::query()->create(['type' => 'system', 'name' => 'Second', 'is_default' => false, 'is_super' => false, 'ordering' => 2]);
        $super = Role::query()->create(['type' => 'system', 'name' => 'Super', 'is_default' => false, 'is_super' => true, 'ordering' => 3]);

        $this->postJson(route('admin.roles.bulk'), ['ids' => [$super->id], 'operation' => 'delete'])->assertUnprocessable();
        Hooks::addAction('jobic.roles.deleting', function (array $context) use ($second): void {
            if ($context['id'] === $second->id) {
                throw ValidationException::withMessages(['ids' => 'Veto']);
            }
        });
        $this->postJson(route('admin.roles.bulk'), ['ids' => [$first->id, $second->id], 'operation' => 'delete'])->assertUnprocessable();
        $this->assertDatabaseHas('roles', ['id' => $first->id]);
        $this->assertDatabaseHas('roles', ['id' => $second->id]);
    }
}
