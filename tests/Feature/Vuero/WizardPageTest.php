<?php

namespace Tests\Feature\Vuero;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WizardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_vuero_login(): void
    {
        $this->get(route('vuero.admin.wizard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_the_complete_wizard(): void
    {
        $this->actingAs(User::factory()->create([
            'password' => 'test-password',
        ]));

        $response = $this->get(route('vuero.admin.wizard'));

        $response
            ->assertOk()
            ->assertSee('Select a project type')
            ->assertSee('What is this project about?')
            ->assertSee('Add more details')
            ->assertSee('Add files to this project')
            ->assertSee('Who will be working on this project?')
            ->assertSee('What tools will you be using?')
            ->assertSee('Make sure everything is good')
            ->assertSee("Congrats! You're all set.", false)
            ->assertSee('data-project-wizard', false)
            ->assertSee('data-wizard-progress', false)
            ->assertSee('/vuero/build/assets/wizard-', false);
    }

    public function test_wizard_route_stays_under_the_vuero_demo_prefix(): void
    {
        $this->assertSame('/vuero/admin/wizard', route('vuero.admin.wizard', absolute: false));
    }

    public function test_wizard_media_is_local_and_available(): void
    {
        foreach (config('vuero.wizard.customers') as $customer) {
            $this->assertFileExists(public_path('vuero/images/wizard/customers/'.$customer['image']));
        }

        foreach (config('vuero.wizard.teammates') as $teammate) {
            $this->assertFileExists(public_path('vuero/images/wizard/people/'.$teammate['image']));
        }

        foreach (config('vuero.wizard.tools') as $tool) {
            $this->assertFileExists(public_path('vuero/images/wizard/tools/'.$tool['image']));
        }
    }
}
