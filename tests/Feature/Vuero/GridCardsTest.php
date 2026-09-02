<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GridCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_grid_card_routes_require_authentication(): void
    {
        foreach (range(1, 4) as $version) {
            $this->get("/vuero/admin/layouts/grid-card-v{$version}")->assertRedirect('/login');
        }
    }

    public function test_grid_card_v1_renders_user_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'grid-card-v1'));

        $response
            ->assertOk()
            ->assertSee('Grid Cards 1')
            ->assertSee('data-grid-card-v1', false)
            ->assertSee('Tara Svenson')
            ->assertSee('Add User')
            ->assertSee('View Profile')
            ->assertSee('/vuero/build/assets/grid-card-v1-', false);
    }

    public function test_grid_card_v2_renders_project_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'grid-card-v2'));

        $response
            ->assertOk()
            ->assertSee('Grid Cards 2')
            ->assertSee('data-grid-card-v2', false)
            ->assertSee('Delivery App Project')
            ->assertSee('New Project')
            ->assertSee('/vuero/build/assets/grid-card-v2-', false);
    }

    public function test_grid_card_v3_renders_lockable_project_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'grid-card-v3'));

        $response
            ->assertOk()
            ->assertSee('Grid Cards 3')
            ->assertSee('data-grid-card-v3', false)
            ->assertSee('h-toggle', false)
            ->assertSee('remaining')
            ->assertSee('/vuero/build/assets/grid-card-v3-', false);
    }

    public function test_grid_card_v4_renders_post_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'grid-card-v4'));

        $response
            ->assertOk()
            ->assertSee('Grid Cards 4')
            ->assertSee('data-grid-card-v4', false)
            ->assertSee('How to serve coffee at the office the proper way')
            ->assertSee('New Post')
            ->assertSee('/vuero/build/assets/grid-card-v4-', false);
    }
}
