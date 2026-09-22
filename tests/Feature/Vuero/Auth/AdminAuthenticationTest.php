<?php

namespace Tests\Feature\Vuero\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_redirects_guests_to_login(): void
    {
        $this->get(route('home'))
            ->assertRedirect(route('login'));
    }

    public function test_home_redirects_authenticated_users_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create([
            'password' => 'test-password',
        ]))
            ->get(route('home'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_completed_installer_redirects_to_login(): void
    {
        config()->set([
            'installation.completed' => true,
        ]);

        $this->get(route('installation.intro'))
            ->assertRedirect(route('login'));
    }

    public function test_guest_can_view_the_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee(trans('auth.screen.heading', [], 'fa'))
            ->assertSee('Jobic');
    }

    public function test_guest_is_redirected_from_the_dashboard_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_settings(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.profile-settings'))
            ->assertOk()
            ->assertSee('Profile Settings')
            ->assertSee('Manage Company');
    }

    public function test_user_can_authenticate_and_open_the_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
        ]);
        $user->assignRole($this->dashboardRole());

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-page="ecommerce-1"', false)
            ->assertSee($user->name);
    }

    public function test_successful_login_carries_the_guest_language_into_the_dashboard(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'ui_preferences' => array_replace(config('admin.appearance'), [
                'locale' => 'en',
                'theme' => 'dark',
            ]),
        ]);
        $user->assignRole($this->dashboardRole());

        $this->withSession(['auth_locale' => 'fa'])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'secret-password',
            ])
            ->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(trans('admin.dashboard', [], 'fa'));

        $preferences = $user->fresh()->ui_preferences;
        $this->assertSame('fa', $preferences['locale']);
        $this->assertSame('dark', $preferences['theme']);
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-password',
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'not-the-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    /**
     * Create a role that grants access to the administration dashboard.
     *
     * @return Role
     */
    private function dashboardRole(): Role
    {
        return Role::query()->create([
            'type' => 'system',
            'name' => 'Dashboard access',
            'allow' => ['admin.dashboard'],
            'deny' => [],
            'is_super' => false,
            'is_default' => false,
            'ordering' => 1,
        ]);
    }

    public function test_logout_preserves_the_selected_authentication_language(): void
    {
        $user = User::factory()->create([
            'password' => 'secret-password',
            'ui_preferences' => array_replace(config('admin.appearance'), ['locale' => 'en']),
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertSee(trans('auth.screen.heading', [], 'en'))
            ->assertDontSee(trans('auth.screen.heading', [], 'fa'));
    }
}
