<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlexListTest extends TestCase
{
    use RefreshDatabase;

    public function test_flex_list_routes_require_authentication(): void
    {
        foreach (['flex-list-v1', 'flex-list-v2', 'flex-list-v3'] as $layout) {
            $this->get("/vuero/admin/layouts/{$layout}")->assertRedirect('/vuero/login');
        }
    }

    public function test_flex_list_v1_renders_the_complete_users_table(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'flex-list-v1'));

        $response
            ->assertOk()
            ->assertSee('Flex List 1')
            ->assertSee('data-flex-list-v1', false)
            ->assertSee('Add User')
            ->assertSee('data-flex-add-user', false)
            ->assertSee('Erik K.')
            ->assertSee('Alan T.')
            ->assertSee('Online')
            ->assertSee('Suspended')
            ->assertSee('/vuero/build/assets/flex-list-v1-', false)
            ->assertSee('/vuero/images/layouts/flex-list/placeholders/search-4.svg', false);
    }

    public function test_flex_list_v2_renders_projects_and_closed_placeholder(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'flex-list-v2'));

        $response
            ->assertOk()
            ->assertSee('Flex List 2')
            ->assertSee('data-flex-list-v2', false)
            ->assertSee('data-layout-tab="active"', false)
            ->assertSee('data-layout-tab="closed"', false)
            ->assertSee('Website Redesign')
            ->assertSee('Corporate Social Network')
            ->assertSee('No closed projects.')
            ->assertSee('/vuero/images/layouts/flex-list/abstracts/1.png', false)
            ->assertSee('/vuero/build/assets/flex-list-v2-', false);
    }

    public function test_flex_list_v3_renders_courses_and_saved_placeholder(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'flex-list-v3'));

        $response
            ->assertOk()
            ->assertSee('Flex List 3')
            ->assertSee('data-flex-list-v3', false)
            ->assertSee('data-layout-tab="all"', false)
            ->assertSee('data-layout-tab="saved"', false)
            ->assertSee('Introduction to Html5')
            ->assertSee('learning Advanced Docker Concepts')
            ->assertSee('No saved courses.')
            ->assertSee('Purchase', false)
            ->assertSee('data-flex-purchase', false)
            ->assertSee('/vuero/images/layouts/flex-list/courses/1.jpg', false)
            ->assertSee('/vuero/build/assets/flex-list-v3-', false);
    }

    public function test_layout_navigation_links_to_all_flex_lists(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'flex-list-v1'));

        foreach (range(1, 3) as $version) {
            $response
                ->assertSee("Flex List V{$version}")
                ->assertSee(route('vuero.admin.layouts.show', "flex-list-v{$version}"), false);
        }

        $response->assertSee('class="is-active" href="'.route('vuero.admin.layouts.show', 'flex-list-v1').'"', false);
    }
}
