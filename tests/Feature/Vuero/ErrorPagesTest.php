<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_error_pages_require_authentication(): void
    {
        $this->get('/vuero/error/page-1')
            ->assertRedirect('/vuero/login');
    }

    public function test_all_five_reference_error_pages_render_their_exact_layout(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));
        $count = 0;

        foreach (config('vuero.error_pages') as $slug => $page) {
            $response = $this->get(route('vuero.error.show', $slug));

            $response
                ->assertOk()
                ->assertSeeText($page['heading'])
                ->assertSeeText($page['text'])
                ->assertSee('Take me Back', false)
                ->assertSee("data-error-page=\"{$slug}\"", false)
                ->assertSee("data-error-reference=\"{$page['reference']}\"", false)
                ->assertSee('/vuero/build/assets/error-page-', false);

            if ($page['bg_number']) {
                $response->assertSee($page['bg_number'], false);
            }

            if ($page['illustration'] === 'svg') {
                $response->assertSee('error-placeholder-clip', false);
            } else {
                $response->assertSee("{$page['illustration']}.svg", false);
                $response->assertSee("{$page['illustration']}-dark.svg", false);
            }

            $count++;
        }

        $this->assertSame(5, $count);
    }

    public function test_invalid_error_page_is_not_available(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));

        $this->get('/vuero/error/page-6')->assertNotFound();
        $this->get('/vuero/error/not-a-page')->assertNotFound();
    }

    public function test_error_page_navigation_lists_every_reference_page(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));
        $response = $this->get('/vuero/admin/layouts/list-view-v1');

        foreach (array_keys(config('vuero.error_pages')) as $slug) {
            $response->assertSee(route('vuero.error.show', $slug, false), false);
        }
    }

    public function test_error_page_route_stays_inside_the_vuero_demo(): void
    {
        $this->assertSame(
            '/vuero/error/page-5',
            route('vuero.error.show', 'page-5', absolute: false),
        );
    }
}
