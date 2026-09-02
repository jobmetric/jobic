<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_layout_routes_require_authentication(): void
    {
        foreach (range(1, 5) as $version) {
            $this->get("/vuero/admin/layouts/form-layout-v{$version}")->assertRedirect('/login');
        }
    }

    public function test_form_layout_v1_renders_demo_request_form(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'form-layout-v1'));

        $response
            ->assertOk()
            ->assertSee('Form Layouts 1')
            ->assertSee('data-form-layout-v1', false)
            ->assertSee('Request a Demo')
            ->assertSee('Personal Info')
            ->assertSee('data-form-multiselect', false)
            ->assertSee('Select a size')
            ->assertSee('Schedule')
            ->assertSee('/vuero/build/assets/form-layout-v1-', false);
    }

    public function test_form_layout_v2_renders_payout_form(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'form-layout-v2'));

        $response
            ->assertOk()
            ->assertSee('Form Layouts 2')
            ->assertSee('data-form-layout-v2', false)
            ->assertSee('Request Payout')
            ->assertSee('Choose an amount')
            ->assertSee('/vuero/build/assets/form-layout-v2-', false);
    }

    public function test_form_layout_v3_renders_separate_order_form(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'form-layout-v3'));

        $response
            ->assertOk()
            ->assertSee('Form Layouts 3')
            ->assertSee('data-form-layout-v3', false)
            ->assertSee('Personal Information')
            ->assertSee('data-form-multiselect', false)
            ->assertSee('Select a country')
            ->assertSee('Confirm My Order')
            ->assertSee('/vuero/build/assets/form-layout-v3-', false);
    }

    public function test_form_layout_v4_renders_meeting_form(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'form-layout-v4'));

        $response
            ->assertOk()
            ->assertSee('Form Layouts 4')
            ->assertSee('data-form-layout-v4', false)
            ->assertSee('Schedule a meeting')
            ->assertSee('Meeting details')
            ->assertSee('/vuero/build/assets/form-layout-v4-', false);
    }

    public function test_form_layout_v5_renders_stepper_form(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'form-layout-v5'));

        $response
            ->assertOk()
            ->assertSee('Form Layouts 5')
            ->assertSee('data-form-layout-v5', false)
            ->assertSee('General Information')
            ->assertSee('data-step-continue', false)
            ->assertSee('/vuero/images/layouts/form-layouts/plant-1.svg', false)
            ->assertSee('/vuero/build/assets/form-layout-v5-', false);
    }

    public function test_layout_navigation_links_to_all_form_layout_pages(): void
    {
        $response = $this->actingAs(User::factory()->create(['password' => 'test-password']))
            ->get(route('vuero.admin.layouts.show', 'form-layout-v1'));

        $response
            ->assertSee('Form Layouts')
            ->assertSee('Form Layout V1')
            ->assertSee('Form Layout V5');

        foreach (range(1, 5) as $version) {
            $response->assertSee(route('vuero.admin.layouts.show', "form-layout-v{$version}"), false);
        }

        $response->assertSee('class="is-active" href="'.route('vuero.admin.layouts.show', 'form-layout-v1').'"', false);
    }
}
