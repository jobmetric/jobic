<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_sidebar_uses_the_vuero_group_order_and_dividers(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.dashboards.show', 'stocks'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '>Personal<',
                '>Finance<',
                '>Banking<',
                '>Business<',
                '>Lifestyle<',
                '>Ecommerce<',
                '>Apps<',
                'role="separator"',
                '>Wizard<',
                'role="separator"',
                '>Charts<',
                '>Widgets<',
                '>Starters<',
            ], false);

        $response
            ->assertDontSee('Sidebar Starters')
            ->assertDontSee('Sideblock Starters')
            ->assertDontSee('Navbar Starters');
    }

    public function test_dashboard_sidebar_uses_the_exact_vuero_link_labels(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.dashboard'));

        $response
            ->assertOk()
            ->assertSee('Stocks Dashboard')
            ->assertSee('Influencer')
            ->assertSee('Apex Charts')
            ->assertSee('Billboard JS')
            ->assertSee('UI Widgets')
            ->assertSee('Creative Widgets')
            ->assertSee('List Widgets')
            ->assertSee('Stat Widgets')
            ->assertSee('Regular Sidebar')
            ->assertSee('Curved Colored Sidebar')
            ->assertSee('Regular Sideblock')
            ->assertSee('Curved Colored')
            ->assertSee('Regular Navbar')
            ->assertSee('Clean Fade Navbar');
    }

    public function test_starters_are_grouped_under_one_vuero_menu_with_family_dividers(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.dashboard'));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '>Starters<',
                '>Regular Sidebar<',
                '>Float Sidebar<',
                'subsidebar-menu__child-divider',
                '>Regular Sideblock<',
                '>Curved Colored<',
                'subsidebar-menu__child-divider',
                '>Regular Navbar<',
                '>Clean Fade Navbar<',
            ], false);
    }

    public function test_sidebar_accordions_have_accessible_unique_controls(): void
    {
        $html = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.dashboard'))
            ->assertOk()
            ->getContent();

        preg_match_all('/data-accordion-toggle aria-controls="([^"]+)"/', $html, $controls);

        $this->assertNotEmpty($controls[1]);
        $this->assertCount(count($controls[1]), array_unique($controls[1]));

        foreach ($controls[1] as $control) {
            $this->assertStringContainsString('id="'.$control.'"', $html);
        }
    }

    public function test_sidebar_groups_are_initially_collapsed_like_vuero(): void
    {
        $html = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.dashboards.show', 'stocks'))
            ->assertOk()
            ->getContent();

        preg_match_all('/data-accordion-toggle[^>]+aria-expanded="([^"]+)"/', $html, $expanded);

        $this->assertNotEmpty($expanded[1]);
        $this->assertSame([], array_values(array_filter($expanded[1], fn (string $value): bool => $value !== 'false')));
        $this->assertStringContainsString('class="is-active" href="'.route('vuero.admin.dashboards.show', 'stocks').'"', $html);
    }
}
