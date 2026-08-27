<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListViewV2V3Test extends TestCase
{
    use RefreshDatabase;

    public function test_list_view_v2_renders_the_complete_property_experience(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-view-v2'));

        $response
            ->assertOk()
            ->assertSee('List View 2')
            ->assertSee('data-list-view-v2', false)
            ->assertSee('data-layout-tab="active"', false)
            ->assertSee('data-layout-tab="inactive"', false)
            ->assertSee('There are no inactive properties.')
            ->assertSee('More Info')
            ->assertSee('Book Now')
            ->assertSee('/vuero/build/assets/list-view-v2-', false);

        foreach (['1396 Redmond Street', '24 Mulberry Street', '62 John Walberg Ave', '48 Manhattan Ave', '12 Charity Street', '23 Bakery Street', '1028 Pasadena Ave', '54 Church Street', '112 Old City District', '51 St Leonard Street'] as $property) {
            $response->assertSee($property);
        }

        foreach (['Parking', 'Wifi', 'Heater', 'Cleaning', '4 more'] as $amenity) {
            $response->assertSee($amenity);
        }
    }

    public function test_list_view_v3_renders_the_complete_jobs_experience(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-view-v3'));

        $response
            ->assertOk()
            ->assertSee('List View 3')
            ->assertSee('data-list-view-v3', false)
            ->assertSee('data-layout-tab="all"', false)
            ->assertSee('data-layout-tab="saved"', false)
            ->assertSee('No saved jobs.')
            ->assertSee('Apply Now')
            ->assertSee('data-job-action="save"', false)
            ->assertSee('/vuero/build/assets/list-view-v3-', false);

        foreach (['Node JS Fullstack Developer wanted', 'Project Manager for React Native Project', 'UI/UX Designer to Consolidate the UX Team', 'IT Team Senior DevOps Manager', 'Fullstack Developer With Solid MongoDB Skills', 'Product Owner for React Native Project', 'Experienced Senior UX Team Manager'] as $job) {
            $response->assertSee($job);
        }

        foreach (['airbnb', 'hubspot', 'slack', 'gitlab', 'google', 'facebook', 'tnw', 'dribbble', 'atlassian', 'github'] as $brand) {
            $response->assertSee("/vuero/images/layouts/list-view-v3/brands/{$brand}.svg", false);
        }
    }

    public function test_layout_navigation_links_to_all_three_list_views(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'list-view-v3'));

        foreach (range(1, 4) as $version) {
            $response
                ->assertSee("List View V{$version}")
                ->assertSee(route('vuero.admin.layouts.show', "list-view-v{$version}"), false);
        }

        $response->assertSee('class="is-active" href="'.route('vuero.admin.layouts.show', 'list-view-v3').'"', false);
    }
}
