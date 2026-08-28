<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GridTilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_grid_tile_routes_require_authentication(): void
    {
        foreach (range(1, 3) as $version) {
            $this->get("/vuero/admin/layouts/grid-tile-v{$version}")->assertRedirect('/vuero/login');
        }
    }

    public function test_grid_tile_v1_renders_user_tiles(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'grid-tile-v1'));

        $response
            ->assertOk()
            ->assertSee('Grid Tiles 1')
            ->assertSee('data-grid-tile-v1', false)
            ->assertSee('Hakeem C.')
            ->assertSee('Add User')
            ->assertSee('Permissions')
            ->assertSee('/vuero/build/assets/grid-tile-v1-', false);
    }

    public function test_grid_tile_v2_renders_file_tiles(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'grid-tile-v2'));

        $response
            ->assertOk()
            ->assertSee('Grid Tiles 2')
            ->assertSee('data-grid-tile-v2', false)
            ->assertSee('Company UX Guide')
            ->assertSee('Add File')
            ->assertSee('Download')
            ->assertSee('/vuero/build/assets/grid-tile-v2-', false);
    }

    public function test_grid_tile_v3_renders_masonry_post_tiles(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'grid-tile-v3'));

        $response
            ->assertOk()
            ->assertSee('Grid Tiles 3')
            ->assertSee('data-grid-tile-v3', false)
            ->assertSee('How to serve coffee at the office the proper way')
            ->assertSee('tile is-ancestor', false)
            ->assertSee('is-tall', false)
            ->assertSee('/vuero/build/assets/grid-tile-v3-', false);
    }
}
