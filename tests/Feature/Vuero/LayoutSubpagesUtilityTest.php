<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutSubpagesUtilityTest extends TestCase
{
    use RefreshDatabase;

  /** @return array<int, string> */
    private function layoutSlugs(): array
    {
        return [
            'saas-billing',
            'action-page-v1',
            'action-page-v2',
            'utility-account-confirm',
            'utility-promotion',
            'utility-invoice',
        ];
    }

    public function test_subpages_and_utility_routes_require_authentication(): void
    {
        foreach ($this->layoutSlugs() as $slug) {
            $this->get("/vuero/admin/layouts/{$slug}")->assertRedirect('/login');
        }

        $this->get('/vuero/roadmap')->assertRedirect('/login');
        $this->get('/vuero/status')->assertRedirect('/login');
    }

    public function test_saas_billing_renders_billing_workspace(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'saas-billing'));

        $response
            ->assertOk()
            ->assertSee('data-saas-billing', false)
            ->assertSee('Select a Plan')
            ->assertSee('Vuero Starter')
            ->assertSee('Payment information')
            ->assertSee('/vuero/build/assets/saas-billing-', false);
    }

    public function test_action_page_v1_renders_invitation_card(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'action-page-v1'));

        $response
            ->assertOk()
            ->assertSee('data-action-page-v1', false)
            ->assertSee('Banking Solution Website')
            ->assertSee('data-action-accept', false)
            ->assertSee('/vuero/build/assets/action-page-v1-', false);
    }

    public function test_action_page_v2_renders_side_instructions(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'action-page-v2'));

        $response
            ->assertOk()
            ->assertSee('data-action-page-v2', false)
            ->assertSee('Additional Instructions')
            ->assertSee('Project Summary')
            ->assertSee('/vuero/build/assets/action-page-v2-', false);
    }

    public function test_utility_account_confirm_renders_confirmation_card(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'utility-account-confirm'));

        $response
            ->assertOk()
            ->assertSee('data-utility-account-confirm', false)
            ->assertSee('Please confirm your account')
            ->assertSee('data-confirm-account', false)
            ->assertSee('/vuero/images/illustrations/placeholders/launch.svg', false)
            ->assertSee('/vuero/build/assets/utility-account-confirm-', false);
    }

    public function test_utility_promotion_renders_upgrade_offer(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'utility-promotion'));

        $response
            ->assertOk()
            ->assertSee('data-utility-promotion', false)
            ->assertSee('Empower your team to do greater things collaboratively')
            ->assertSee('Includes 8 team seats')
            ->assertSee('/vuero/build/assets/utility-promotion-', false);
    }

    public function test_utility_invoice_renders_editable_invoice_table(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'utility-invoice'));

        $response
            ->assertOk()
            ->assertSee('data-utility-invoice', false)
            ->assertSee('Invoice FC-456-14')
            ->assertSee('Website Redesign')
            ->assertSee('data-invoice-quantity', false)
            ->assertSee('data-th="Quantity"', false)
            ->assertSee('invoice-user-card', false)
            ->assertSee('/vuero/build/assets/utility-invoice-', false);
    }

    public function test_utility_roadmap_renders_all_interactive_sections(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.utility.roadmap'));

        $response
            ->assertOk()
            ->assertSee('data-utility-roadmap', false)
            ->assertSee('Our Roadmap')
            ->assertSee('data-roadmap-year', false)
            ->assertSee('data-roadmap-quarter="4"', false)
            ->assertSee('data-utility-panel="changelog"', false)
            ->assertSee('Ecommerce storefront configuration')
            ->assertSee('/vuero/build/assets/utility-roadmap-', false);
    }

    public function test_utility_status_renders_service_health_and_updates(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.utility.status'));

        $response
            ->assertOk()
            ->assertSee('data-utility-status', false)
            ->assertSee('All services are online.')
            ->assertSee('REST API')
            ->assertSee('Integrations')
            ->assertSee('/vuero/images/utility/animaticons/lightbulb.gif', false)
            ->assertSee('/vuero/build/assets/utility-status-', false);
    }

    public function test_sidebar_links_to_subpages_and_utility_layouts(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'saas-billing'));

        foreach ($this->layoutSlugs() as $slug) {
            $response->assertSee(route('vuero.admin.layouts.show', $slug), false);
        }

        $response->assertSee(route('vuero.utility.roadmap'), false);
        $response->assertSee(route('vuero.utility.status'), false);

        $response->assertSee('class="is-active" href="'.route('vuero.admin.layouts.show', 'saas-billing').'"', false);
    }
}
