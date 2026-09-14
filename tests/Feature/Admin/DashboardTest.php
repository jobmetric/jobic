<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_cms_dashboard(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_cms_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-page="ecommerce-1"', false)
            ->assertSee('ecommerce-tile-grid')
            ->assertSee(route('admin.settings.mail.edit'))
            ->assertDontSee('Erik Kovalsky')
            ->assertSee($user->name);
    }

    public function test_cms_dashboard_route_is_under_admin_prefix(): void
    {
        $this->assertSame('/admin', route('admin.dashboard', absolute: false));
    }
}
