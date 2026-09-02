<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_vuero_dashboard_page_can_be_rendered(): void
    {
        $this->actingAs(User::factory()->create([
            'password' => 'test-password',
        ]));

        foreach (config('vuero.dashboards.groups') as $group) {
            foreach ($group['pages'] as $slug => $title) {
                $this->get(route('vuero.admin.dashboards.show', $slug))
                    ->assertOk()
                    ->assertSee($title);
            }
        }
    }

    public function test_vuero_routes_are_kept_under_the_demo_prefix(): void
    {
        $this->get('/vuero/login')->assertRedirect('/login');
        $this->assertSame('/vuero/admin', route('vuero.admin.dashboard', absolute: false));
        $this->assertSame(
            '/vuero/admin/dashboards/analytics',
            route('vuero.admin.dashboards.show', 'analytics', absolute: false),
        );
    }
}
