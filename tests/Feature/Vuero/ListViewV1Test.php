<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListViewV1Test extends TestCase
{
    use RefreshDatabase;

    public function test_list_view_v1_requires_authentication(): void
    {
        $this->get('/vuero/admin/layouts/list-view-v1')->assertRedirect('/vuero/login');
    }

    public function test_list_view_v1_renders_the_complete_vuero_dataset_and_controls(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-view-v1'));

        $response
            ->assertOk()
            ->assertSee('List View 1')
            ->assertSee('data-list-view-v1', false)
            ->assertSee('data-list-filter', false)
            ->assertSee('data-list-count>10</span>', false)
            ->assertSee('data-list-count-label>records found</span>', false)
            ->assertSee('Approve')
            ->assertSee('find any matching results')
            ->assertSee('data-list-pagination', false)
            ->assertSee('/vuero/build/assets/list-view-v1-', false);

        foreach (['Mary Lebowski', 'Daniel Redbird', 'Erik Kovalsky', 'Jeanne Marchand', 'Carmen Escudero', 'Anna Baker', 'Esteban Castellanos', 'Henry Grobstone', 'Melany Wallace', 'Alice Carasca'] as $name) {
            $response->assertSee($name);
        }

        $response
            ->assertSeeInOrder(['Profile', 'View profile', 'Message', 'Send Message', 'Transfer', 'Transfer to other list', 'Remove', 'Remove from list'])
            ->assertSee('data-theme-toggle', false)
            ->assertSee('data-circular-menu', false);
    }

    public function test_layout_navigation_links_to_and_activates_list_view_v1(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-view-v1'));

        $response
            ->assertOk()
            ->assertSee('aria-label="Layouts"', false)
            ->assertSee('>Lists<', false)
            ->assertSee('class="is-active" href="'.route('vuero.admin.layouts.show', 'list-view-v1').'"', false);
    }

    public function test_unknown_layout_is_not_available(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get('/vuero/admin/layouts/not-a-layout')
            ->assertNotFound();
    }
}
