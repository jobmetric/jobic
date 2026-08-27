<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WidgetsPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_widget_pages(): void
    {
        $this->get('/vuero/admin/dashboards/widgets-sample-ui')->assertRedirect('/vuero/login');
    }

    public function test_each_widget_page_renders_its_exact_view_and_entry_script(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));

        foreach ([
            'widgets-sample-ui' => 'Widgets UI',
            'widgets-sample-creative' => 'Widgets Creative',
            'widgets-sample-list' => 'Widgets List',
            'widgets-sample-stats' => 'Widgets Stats',
        ] as $slug => $title) {
            $this->get("/vuero/admin/dashboards/{$slug}")
                ->assertOk()
                ->assertSee($title)
                ->assertSee("data-dashboard-page=\"{$slug}\"", false)
                ->assertSee("/vuero/build/assets/{$slug}-", false);
        }
    }

    public function test_widget_pages_expose_the_reference_content_and_interaction_hooks(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));

        $list = $this->get('/vuero/admin/dashboards/widgets-sample-list');
        $list
            ->assertSee('User Progress')
            ->assertSee('data-widget-tabs="files"', false)
            ->assertSee('data-widget-panel="todo:1"', false)
            ->assertSee('data-widget-menu-toggle', false);
        $this->assertSame(12, substr_count($list->getContent(), 'data-widget-card'));

        $ui = $this->get('/vuero/admin/dashboards/widgets-sample-ui');
        $ui
            ->assertSee('Gauge Widget')
            ->assertSee('data-widget-calendar', false)
            ->assertSee('data-widget-lightbox', false)
            ->assertSee('data-widget-chart="trend"', false);
        $this->assertSame(16, substr_count($ui->getContent(), 'data-widget-card'));
        $this->assertSame(1, substr_count($ui->getContent(), 'data-widget-chart='));

        $creative = $this->get('/vuero/admin/dashboards/widgets-sample-creative');
        $creative
            ->assertSee('You unlocked 2 new Achievements')
            ->assertSee('data-widget-checkout', false)
            ->assertSee('data-call-action="accept"', false)
            ->assertSee('Reload Widget')
            ->assertSee('Configure widget')
            ->assertSee('Widget Settings')
            ->assertSee('Remove from view')
            ->assertSee('role="menu"', false)
            ->assertSee('aria-haspopup="menu"', false);
        $this->assertSame(17, substr_count($creative->getContent(), 'data-widget-card'));
        $this->assertSame(3, substr_count($creative->getContent(), 'data-widget-menu-dropdown'));
        $this->assertSame(12, substr_count($creative->getContent(), 'data-widget-action='));

        $stats = $this->get('/vuero/admin/dashboards/widgets-sample-stats');
        $stats
            ->assertSee('Consolidated Profit')
            ->assertSee('data-widget-chart="area-revenue"', false)
            ->assertSee('data-widget-add-member', false);
        $this->assertSame(15, substr_count($stats->getContent(), 'data-widget-card'));
        $this->assertSame(14, substr_count($stats->getContent(), 'data-widget-chart='));
    }

    public function test_widget_group_is_present_in_sidebar_navigation(): void
    {
        $this->actingAs(User::factory()->create(['password' => 'test-password']));

        $this->get('/vuero/admin/dashboards/widgets-sample-ui')
            ->assertOk()
            ->assertSee('Widgets')
            ->assertSee('/vuero/admin/dashboards/widgets-sample-creative', false)
            ->assertSee('/vuero/admin/dashboards/widgets-sample-list', false)
            ->assertSee('/vuero/admin/dashboards/widgets-sample-stats', false);
    }
}
