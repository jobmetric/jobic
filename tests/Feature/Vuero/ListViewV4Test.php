<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListViewV4Test extends TestCase
{
    use RefreshDatabase;

    public function test_list_view_v4_requires_authentication(): void
    {
        $this->get('/vuero/admin/layouts/list-view-v4')->assertRedirect('/vuero/login');
    }

    public function test_list_view_v4_renders_the_complete_recipes_experience(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-view-v4'));

        $response
            ->assertOk()
            ->assertSee('List View 4')
            ->assertSee('data-list-view-v4', false)
            ->assertSee('data-layout-tab="all"', false)
            ->assertSee('data-layout-tab="saved"', false)
            ->assertSee('No saved recipes.')
            ->assertSee('View Recipe')
            ->assertSee('data-recipe-action="save"', false)
            ->assertSee('Like this')
            ->assertSee('/vuero/build/assets/list-view-v4-', false);

        foreach (['Strawberry & Cream Cake', 'Orange & Lemon Cake', 'Three Chocolates Cake', 'Wedding Cake', 'Vanilla Waffles', 'Marmelade Donuts', 'Peanut Butter Sandwich', 'Strawberry Spicy'] as $recipe) {
            $response->assertSee($recipe);
        }

        foreach (['cake-1.svg', 'cake-2.svg', 'cake-9.svg'] as $icon) {
            $response->assertSee("/vuero/images/layouts/list-view-v4/illustrations/{$icon}", false);
        }

        $response->assertSee('/vuero/images/layouts/list-view-v4/placeholders/cooking.svg', false);
    }

    public function test_layout_navigation_links_to_all_four_list_views(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-view-v4'));

        foreach (range(1, 4) as $version) {
            $response
                ->assertSee("List View V{$version}")
                ->assertSee(route('vuero.admin.layouts.show', "list-view-v{$version}"), false);
        }

        $response->assertSee('class="is-active" href="'.route('vuero.admin.layouts.show', 'list-view-v4').'"', false);
    }
}
