<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlaceloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_placeload_routes_require_authentication(): void
    {
        foreach (range(1, 4) as $version) {
            $this->get("/vuero/admin/layouts/placeload-v{$version}")->assertRedirect('/login');
        }
    }

    public function test_placeload_v1_renders_list_skeleton(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'placeload-v1'));

        $response
            ->assertOk()
            ->assertSee('Placeload V1')
            ->assertSee('data-placeload-v1', false)
            ->assertSee('Loading records')
            ->assertSee('placeload-avatar', false)
            ->assertSee('content-shape loads', false)
            ->assertSee('/vuero/build/assets/placeload-v1-', false);
    }

    public function test_placeload_v2_renders_flex_table_skeleton(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'placeload-v2'));

        $response
            ->assertOk()
            ->assertSee('Placeload V2')
            ->assertSee('data-placeload-v2', false)
            ->assertSee('flex-table-header')
            ->assertSee('Add User')
            ->assertSee('/vuero/build/assets/placeload-v2-', false);
    }

    public function test_placeload_v3_renders_user_grid_skeleton(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'placeload-v3'));

        $response
            ->assertOk()
            ->assertSee('Placeload V3')
            ->assertSee('data-placeload-v3', false)
            ->assertSee('user-grid-v1')
            ->assertSee('placeload-button', false)
            ->assertSee('/vuero/build/assets/placeload-v3-', false);
    }

    public function test_placeload_v4_renders_tile_grid_skeleton(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'placeload-v4'));

        $response
            ->assertOk()
            ->assertSee('Placeload V4')
            ->assertSee('data-placeload-v4', false)
            ->assertSee('tile-grid-v1')
            ->assertSee('tile-grid-item', false)
            ->assertSee('/vuero/build/assets/placeload-v4-', false);
    }
}
