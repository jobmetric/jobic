<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileLayoutTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function profileLayoutUrls(): array
    {
        return [
            route('vuero.admin.layouts.profile-view'),
            route('vuero.admin.layouts.profile-notifications'),
            route('vuero.admin.layouts.profile-edit', 'general'),
            route('vuero.admin.layouts.profile-edit', 'experience'),
            route('vuero.admin.layouts.profile-edit', 'skills'),
            route('vuero.admin.layouts.profile-edit', 'settings'),
            route('vuero.admin.layouts.search-results'),
            route('vuero.admin.layouts.search-empty'),
        ];
    }

    public function test_profile_layout_routes_require_authentication(): void
    {
        foreach ($this->profileLayoutUrls() as $url) {
            $this->get($url)->assertRedirect('/vuero/login');
        }
    }

    public function test_profile_view_renders_profile_sections(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.profile-view'));

        $response
            ->assertOk()
            ->assertSee('Profile View')
            ->assertSee('data-profile-view', false)
            ->assertSee('About Me')
            ->assertSee('Experience')
            ->assertSee('Recommendations')
            ->assertSee('/vuero/build/assets/profile-view-', false);
    }

    public function test_profile_notifications_renders_timeline(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.profile-notifications'));

        $response
            ->assertOk()
            ->assertSee('Profile Notifications')
            ->assertSee('data-profile-notifications', false)
            ->assertSee('Barber Website Redesign Project')
            ->assertSee('Load More')
            ->assertSee('/vuero/build/assets/profile-notifications-', false);
    }

    public function test_profile_edit_tabs_render(): void
    {
        $user = User::factory()->create(['password' => 'test-password']);

        $this->actingAs($user)
            ->get(route('vuero.admin.layouts.profile-edit', 'general'))
            ->assertOk()
            ->assertSee('General Info')
            ->assertSee('Social Profiles')
            ->assertSee('data-profile-edit', false);

        $this->actingAs($user)
            ->get(route('vuero.admin.layouts.profile-edit', 'experience'))
            ->assertOk()
            ->assertSee('Work Experience')
            ->assertSee('Airbnb HQ');

        $this->actingAs($user)
            ->get(route('vuero.admin.layouts.profile-edit', 'skills'))
            ->assertOk()
            ->assertSee('Skills and Tools')
            ->assertSee('Languages');

        $this->actingAs($user)
            ->get(route('vuero.admin.layouts.profile-edit', 'settings'))
            ->assertOk()
            ->assertSee('Change Password')
            ->assertSee('2 Factor Auth');
    }

    public function test_search_results_renders_facet_groups(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.search-results'));

        $response
            ->assertOk()
            ->assertSee('Search Results')
            ->assertSee('data-search-results', false)
            ->assertSee('54 results found')
            ->assertSee('Publication')
            ->assertSee('Alice Carasca')
            ->assertSee('Delivery App Project')
            ->assertSee('/vuero/build/assets/search-results-', false);
    }

    public function test_search_empty_renders_placeholder(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.search-empty'));

        $response
            ->assertOk()
            ->assertSee('Empty Search')
            ->assertSee('data-search-empty', false)
            ->assertSee('0 results found')
            ->assertSee("We couldn't find any matching results.", false)
            ->assertSee('/vuero/images/layouts/search/placeholders/search-7.svg', false)
            ->assertSee('/vuero/build/assets/search-empty-', false);
    }

    public function test_layout_navigation_links_to_profile_and_search_pages(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.profile-view'));

        $response
            ->assertSee('Personal')
            ->assertSee('Profile')
            ->assertSee('Edit Profile')
            ->assertSee('Notifications')
            ->assertSee('Search Results')
            ->assertSee('Empty Search')
            ->assertSee(route('vuero.admin.profile-settings'), false)
            ->assertSee(route('vuero.admin.layouts.profile-view'), false)
            ->assertSee(route('vuero.admin.layouts.search-results'), false);
    }
}
