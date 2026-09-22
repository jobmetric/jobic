<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

/**
 * Verify CMS route enforcement and permission-aware navigation.
 */
class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Deny direct page requests and hide complete unauthorized menu branches.
     */
    public function test_direct_requests_are_denied_and_unauthorized_menus_are_hidden(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'type' => 'system',
            'name' => 'Dashboard viewer',
            'allow' => ['admin.dashboard'],
            'deny' => [],
            'is_super' => false,
            'is_default' => false,
            'ordering' => 1,
        ]);
        $user->assignRole($role);

        parent::actingAs($user);

        $this->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee(__('errors.403.title'))
            ->assertSee('admin-access-denied', false);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.roles.index'), false)
            ->assertDontSee('sidebar-panel-settings', false);
    }

    /**
     * Inherit parent permission grants while allowing explicit child denials.
     */
    public function test_parent_grants_and_child_denials_protect_view_and_manage_routes(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'type' => 'system',
            'name' => 'User viewer',
            'allow' => ['admin.users'],
            'deny' => ['admin.users.manage'],
            'is_super' => false,
            'is_default' => false,
            'ordering' => 1,
        ]);
        $user->assignRole($role);

        parent::actingAs($user);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(route('admin.users.index'), false)
            ->assertDontSee(route('admin.roles.index'), false);
        $this->get(route('admin.users.create'))->assertForbidden();
        $this->postJson(route('admin.users.store'), [])->assertForbidden();
    }

    /**
     * Send a signed-in user to the first administration page allowed by their role.
     */
    public function test_login_uses_the_first_permitted_page_when_dashboard_is_denied(): void
    {
        $user = User::factory()->create([
            'email' => 'role-editor@example.com',
            'password' => 'secret-password',
        ]);
        $role = Role::query()->create([
            'type' => 'system',
            'name' => 'Role editor',
            'allow' => ['admin.roles'],
            'deny' => [],
            'is_super' => false,
            'is_default' => false,
            'ordering' => 1,
        ]);
        $user->assignRole($role);

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.roles.index', absolute: false));

        $this->get(route('admin.roles.index'))->assertOk();
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.roles.index'));
    }

    /**
     * Keep personal presentation controls available on an unauthorized page.
     */
    public function test_personal_controls_remain_available_without_settings_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'type' => 'system',
            'name' => 'Dashboard only',
            'allow' => ['admin.dashboard'],
            'deny' => [],
            'is_super' => false,
            'is_default' => false,
            'ordering' => 1,
        ]);
        $user->assignRole($role);

        parent::actingAs($user);

        $this->get(route('admin.users.index'))
            ->assertForbidden()
            ->assertSee('data-cms-locale="en"', false)
            ->assertSee('data-theme-toggle', false);

        $this->putJson(route('admin.account.appearance.update'), [
            'layout' => 'sidebar-default',
            'theme' => 'dark',
            'motion' => 'full',
            'locale' => 'en',
        ])->assertOk();

        $this->get(route('admin.account.appearance.edit'))
            ->assertOk()
            ->assertSee(route('admin.account.profile.edit'), false)
            ->assertSee(route('admin.account.security.edit'), false);
        $this->get(route('admin.account.profile.edit'))
            ->assertOk()
            ->assertSee(route('admin.account.security.edit'), false);
        $this->get(route('admin.account.security.edit'))->assertOk();
    }

    /**
     * Enforce a separate permission for every settings menu item.
     */
    public function test_each_settings_item_has_an_independent_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'type' => 'system',
            'name' => 'Cache operator',
            'allow' => ['admin.settings.cache'],
            'deny' => [],
            'is_super' => false,
            'is_default' => false,
            'ordering' => 1,
        ]);
        $user->assignRole($role);

        parent::actingAs($user);

        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee(route('admin.settings.cache.edit'), false)
            ->assertDontSee(route('admin.settings.health'), false)
            ->assertDontSee(route('admin.settings.mail.edit'), false)
            ->assertDontSee(route('admin.settings.languages.index'), false)
            ->assertDontSee(route('admin.settings.edit', 'seo'), false)
            ->assertDontSee(route('admin.settings.edit', 'general'), false);
        $this->get(route('admin.settings.cache.edit'))->assertOk();
        $this->putJson(route('admin.settings.cache.update'), [])->assertUnprocessable();
        $this->get(route('admin.settings.health'))->assertForbidden();
        $this->get(route('admin.settings.mail.edit'))->assertForbidden();
        $this->get(route('admin.settings.languages.index'))->assertForbidden();
        $this->get(route('admin.settings.edit', 'seo'))->assertForbidden();
        $this->get(route('admin.settings.edit', 'general'))->assertForbidden();
    }
}
