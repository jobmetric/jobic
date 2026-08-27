<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChartsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_chart_pages_require_authentication(): void
    {
        foreach (['charts-apex', 'charts-billboardjs'] as $dashboard) {
            $this->get(route('vuero.admin.dashboards.show', $dashboard))
                ->assertRedirect(route('vuero.login'));
        }
    }

    public function test_apex_dashboard_contains_every_vuero_chart(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.dashboards.show', 'charts-apex'));

        $response
            ->assertOk()
            ->assertSee('Charts Apex')
            ->assertSee('data-charts-grid="apex"', false)
            ->assertSee('data-apex-chart="radar"', false)
            ->assertSee('/vuero/build/assets/charts-apex-', false);

        $this->assertSame(22, substr_count($response->getContent(), 'data-apex-chart='));
    }

    public function test_billboard_dashboard_contains_every_vuero_chart(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.dashboards.show', 'charts-billboardjs'));

        $response
            ->assertOk()
            ->assertSee('Charts BillboardJS')
            ->assertSee('data-charts-grid="billboard"', false)
            ->assertSee('data-billboard-chart="radar"', false)
            ->assertSee('/vuero/build/assets/charts-billboardjs-', false);

        $this->assertSame(14, substr_count($response->getContent(), 'data-billboard-chart='));
    }

    public function test_chart_routes_stay_inside_the_vuero_demo(): void
    {
        $this->assertSame(
            '/vuero/admin/dashboards/charts-apex',
            route('vuero.admin.dashboards.show', 'charts-apex', absolute: false),
        );
        $this->assertSame(
            '/vuero/admin/dashboards/charts-billboardjs',
            route('vuero.admin.dashboards.show', 'charts-billboardjs', absolute: false),
        );
    }
}
