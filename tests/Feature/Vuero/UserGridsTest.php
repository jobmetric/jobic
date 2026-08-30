<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserGridsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_grid_routes_require_authentication(): void
    {
        foreach (range(1, 4) as $version) {
            $this->get("/vuero/admin/layouts/user-grid-v{$version}")->assertRedirect('/vuero/login');
        }
    }

    public function test_user_grid_v1_renders_user_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'user-grid-v1'));

        $response
            ->assertOk()
            ->assertSee('User Grid 1')
            ->assertSee('data-user-grid-v1', false)
            ->assertSee('Tara Svenson')
            ->assertSee('Add User')
            ->assertSee('Profile')
            ->assertSee('/vuero/build/assets/user-grid-v1-', false);
    }

    public function test_user_grid_v2_renders_status_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'user-grid-v2'));

        $response
            ->assertOk()
            ->assertSee('User Grid 2')
            ->assertSee('data-user-grid-v2', false)
            ->assertSee('In Sync')
            ->assertSee('tasks remaining')
            ->assertSee('/vuero/build/assets/user-grid-v2-', false);
    }

    public function test_user_grid_v3_renders_tabbed_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'user-grid-v3'));

        $response
            ->assertOk()
            ->assertSee('User Grid 3')
            ->assertSee('data-user-grid-v3', false)
            ->assertSee('No team members.')
            ->assertSee('Viewers')
            ->assertSee('/vuero/build/assets/user-grid-v3-', false);
    }

    public function test_user_grid_v4_renders_dropdown_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'user-grid-v4'));

        $response
            ->assertOk()
            ->assertSee('User Grid 4')
            ->assertSee('data-user-grid-v4', false)
            ->assertSee('Add To Project')
            ->assertSee('Add To Team')
            ->assertSee('Permissions')
            ->assertSee('/vuero/build/assets/user-grid-v4-', false);
    }
}
