<?php

namespace Tests\Feature\Vuero\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_disabled_installer_redirects_to_the_application(): void
    {
        config()->set([
            'installation.enabled' => false,
            'installation.completed' => false,
        ]);

        $this->get(route('installation.intro'))
            ->assertRedirect(route('home'));
    }

    public function test_guest_can_view_the_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('ورود به حساب')
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

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('داشبورد')
            ->assertSee($user->name);
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
}
