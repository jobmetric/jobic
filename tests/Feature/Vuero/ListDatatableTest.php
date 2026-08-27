<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListDatatableTest extends TestCase
{
    use RefreshDatabase;

    public function test_datatable_routes_require_authentication(): void
    {
        foreach (['list-datatable-v1', 'list-datatable-v2', 'list-datatable-v3'] as $layout) {
            $this->get("/vuero/admin/layouts/{$layout}")->assertRedirect('/vuero/login');
        }
    }

    public function test_list_datatable_v1_renders_the_complete_users_table(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-datatable-v1'));

        $response
            ->assertOk()
            ->assertSee('Table List 1')
            ->assertSee('data-list-datatable-v1', false)
            ->assertSee('Add User')
            ->assertSee('data-datatable-cta', false)
            ->assertSee('Erik K.')
            ->assertSee('Alan T.')
            ->assertSee('datatable-table', false)
            ->assertSee('/vuero/build/assets/list-datatable-v1-', false)
            ->assertSee('/vuero/images/layouts/list-datatable/placeholders/search-7.svg', false);
    }

    public function test_list_datatable_v2_renders_projects_table(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-datatable-v2'));

        $response
            ->assertOk()
            ->assertSee('Table List 2')
            ->assertSee('data-list-datatable-v2', false)
            ->assertSee('New Project')
            ->assertSee('Website Redesign')
            ->assertSee('Corporate Social Network')
            ->assertSee('In Progress')
            ->assertSee('/vuero/build/assets/list-datatable-v2-', false);
    }

    public function test_list_datatable_v3_renders_offers_table(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-datatable-v3'));

        $response
            ->assertOk()
            ->assertSee('Table List 3')
            ->assertSee('data-list-datatable-v3', false)
            ->assertSee('New Offer')
            ->assertSee('Apply Now')
            ->assertSee('data-datatable-apply', false)
            ->assertSee('Node JS Fullstack Developer wanted')
            ->assertSee('/vuero/images/layouts/list-view-v3/brands/airbnb.svg', false)
            ->assertSee('/vuero/build/assets/list-datatable-v3-', false);
    }

    public function test_layout_navigation_links_to_all_datatable_pages(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-datatable-v1'));

        $response
            ->assertSee('Datatable')
            ->assertSee('Table List V1')
            ->assertSee('Table List V2')
            ->assertSee('Table List V3');

        foreach (range(1, 3) as $version) {
            $response->assertSee(route('vuero.admin.layouts.show', "list-datatable-v{$version}"), false);
        }

        $response->assertSee('class="is-active" href="'.route('vuero.admin.layouts.show', 'list-datatable-v1').'"', false);
    }
}
