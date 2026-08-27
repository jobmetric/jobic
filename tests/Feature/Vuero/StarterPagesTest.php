<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StarterPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_pages_require_authentication(): void
    {
        $this->get('/vuero/admin/starters/sidebar-blank-page-1')
            ->assertRedirect('/vuero/login');
    }

    public function test_all_nineteen_reference_starters_render_their_exact_layout(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));
        $count = 0;

        foreach (config('vuero.starters') as $family => $group) {
            foreach ($group['pages'] as $slug => $starter) {
                $response = $this->get(route('vuero.admin.starters.show', $slug));

                $response
                    ->assertOk()
                    ->assertSee($starter['title'])
                    ->assertSee("data-starter-page=\"{$slug}\"", false)
                    ->assertSee("data-starter-family=\"{$family}\"", false)
                    ->assertSee("data-starter-layout=\"{$starter['layout']}\"", false)
                    ->assertSee("data-initial-layout=\"{$starter['layout']}\"", false)
                    ->assertSee("data-starter-reference=\"{$starter['reference']}\"", false)
                    ->assertSee('/vuero/build/assets/starter-', false);

                if ($family === 'sideblock') {
                    $response->assertSee('data-open-navigation="true"', false);
                }

                $count++;
            }
        }

        $this->assertSame(19, $count);
    }

    public function test_invalid_starter_is_not_available(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));

        $this->get('/vuero/admin/starters/sidebar-blank-page-8')->assertNotFound();
        $this->get('/vuero/admin/starters/not-a-starter')->assertNotFound();
    }

    public function test_starter_navigation_lists_every_reference_page(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));
        $response = $this->get('/vuero/admin/starters/sidebar-blank-page-1');

        foreach (config('vuero.starters') as $group) {
            foreach (array_keys($group['pages']) as $slug) {
                $response->assertSee(route('vuero.admin.starters.show', $slug, false), false);
            }
        }
    }

    public function test_starter_route_stays_inside_the_vuero_demo(): void
    {
        $this->assertSame(
            '/vuero/admin/starters/navbar-blank-page-8',
            route('vuero.admin.starters.show', 'navbar-blank-page-8', absolute: false),
        );
    }
}
