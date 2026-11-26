<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions;

use App\Extensions\Module\JobicMenu\JobicMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Extension\Models\Extension;
use Tests\TestCase;

class InstanceValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_instance_validation_uses_the_form_label_in_both_languages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $extension = Extension::query()->create([
            'extension' => 'Module',
            'name' => 'JobicMenu',
            'namespace' => JobicMenu::class,
            'info' => ['multiple' => true],
        ]);
        $url = route('admin.settings.extensions.plugins.store', $extension->id);

        foreach (['en' => 'Select a menu.', 'fa' => 'یک منو انتخاب کنید.'] as $locale => $message) {
            $user->forceFill(['ui_preferences' => ['locale' => $locale]])->save();
            $response = $this->postJson($url, ['name' => 'Navigation', 'status' => 1, 'fields' => []])
                ->assertUnprocessable()->assertJsonPath('message', $message);
            $this->assertSame([$message], $response->json('errors')['fields.menu_id']);

            $invalid = $this->postJson($url, ['name' => 'Navigation', 'status' => 1, 'fields' => ['menu_id' => 9999]])
                ->assertUnprocessable();
            $this->assertSame([
                trans('validation.exists', ['attribute' => trans('site.extensions.menu', locale: $locale)], $locale),
            ], $invalid->json('errors')['fields.menu_id']);
        }
    }
}
