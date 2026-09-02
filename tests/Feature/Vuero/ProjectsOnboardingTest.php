<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectsOnboardingTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<string> */
    private function projectLayouts(): array
    {
        return ['projects-v1', 'projects-v2', 'projects-v3', 'project-details', 'kanban-board'];
    }

    /** @return list<string> */
    private function onboardingLayouts(): array
    {
        return ['onboarding-welcome', 'onboarding-v1', 'onboarding-v2', 'onboarding-v3', 'onboarding-v4', 'onboarding-v5'];
    }

    public function test_project_and_onboarding_routes_require_authentication(): void
    {
        foreach ([...$this->projectLayouts(), ...$this->onboardingLayouts()] as $layout) {
            $this->get("/vuero/admin/layouts/{$layout}")->assertRedirect('/login');
        }
    }

    public function test_projects_v1_renders_toolbar_recent_grid_and_table(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'projects-v1'));

        $response
            ->assertOk()
            ->assertSee('Projects projects')
            ->assertSee('data-projects-v1', false)
            ->assertSee('Recently viewed')
            ->assertSee('The Slicer project')
            ->assertSee('Erik K.')
            ->assertSee('New Project')
            ->assertSee('/vuero/build/assets/projects-v1-', false);
    }

    public function test_projects_v2_renders_stats_and_project_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'projects-v2'));

        $response
            ->assertOk()
            ->assertSee('Projects projects 2')
            ->assertSee('data-projects-v2', false)
            ->assertSee('Published projects')
            ->assertSee('#118 Agios Inc. Rebranding')
            ->assertSee('Archive this project')
            ->assertSee('/vuero/build/assets/projects-v2-', false);
    }

    public function test_projects_v3_renders_illustration_header_and_filter(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'projects-v3'));

        $response
            ->assertOk()
            ->assertSee('Projects projects 3')
            ->assertSee('data-projects-v3', false)
            ->assertSee('Recent Projects')
            ->assertSee('Finance App Onboarding Revamp')
            ->assertSee('/vuero/images/projects/header.svg', false)
            ->assertSee('data-form-multiselect', false)
            ->assertSee('/vuero/build/assets/projects-v3-', false);
    }

    public function test_project_details_renders_tabs_and_files(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'project-details'));

        $response
            ->assertOk()
            ->assertSee('Project Details')
            ->assertSee('data-project-details', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('aria-controls="project-panel-project"', false)
            ->assertSee('role="tabpanel"', false)
            ->assertSee('Brand and Website Redesign')
            ->assertSee('Recent Files')
            ->assertSee('Preliminary Sketches')
            ->assertSee('Download this file')
            ->assertSee('View member profile')
            ->assertSee('/vuero/build/assets/project-details-', false);
    }

    public function test_kanban_board_renders_columns_and_tasks(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'kanban-board'));

        $response
            ->assertOk()
            ->assertSee('Kanban Board')
            ->assertSee('data-kanban-board', false)
            ->assertSee('Create prototype with JWT authentication')
            ->assertSee('Implement the users REST API')
            ->assertSee('/vuero/images/projects/board/test.svg', false)
            ->assertSee('data-kanban-column="review"', false)
            ->assertSee('data-kanban-rename="progress"', false)
            ->assertSee('Locks the column')
            ->assertSee('/vuero/build/assets/kanban-board-', false);
    }

    public function test_onboarding_welcome_renders_standard_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'onboarding-welcome'));

        $response
            ->assertOk()
            ->assertSee('Onboarding Welcome')
            ->assertSee('data-onboarding-page="standard"', false)
            ->assertSee('Configure Shop')
            ->assertSee('/vuero/images/onboarding/set1-1.svg', false)
            ->assertSee('/vuero/build/assets/onboarding-welcome-', false);
    }

    public function test_onboarding_v1_renders_wrapper_cards(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'onboarding-v1'));

        $response
            ->assertOk()
            ->assertSee('Onboarding V1')
            ->assertSee('data-onboarding-page="v1"', false)
            ->assertSee('Explore Posts')
            ->assertSee('/vuero/images/onboarding/set2-1.svg', false)
            ->assertSee('/vuero/build/assets/onboarding-page-', false);
    }

    public function test_layout_navigation_links_to_projects_and_onboarding_pages(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'projects-v1'));

        foreach ($this->projectLayouts() as $layout) {
            $response->assertSee(route('vuero.admin.layouts.show', $layout), false);
        }

        foreach ($this->onboardingLayouts() as $layout) {
            $response->assertSee(route('vuero.admin.layouts.show', $layout), false);
        }
    }
}
