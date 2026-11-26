<?php

declare(strict_types=1);

namespace Tests\Feature\Extensions;

use App\Extensions\Module\JobicMenu\JobicMenu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Extension\Models\Extension;
use JobMetric\Taxonomy\Models\Taxonomy;
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
            $this->withoutVite()->get(route('admin.settings.extensions.plugins.create', $extension->id))
                ->assertOk()->assertSee('<option value="" data-select-placeholder>'
                    .trans('admin.options.select', locale: $locale).'</option>', false);

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

    public function test_menu_instances_save_independent_display_settings(): void
    {
        $this->withoutVite()->actingAs(User::factory()->create());
        $extension = Extension::query()->create([
            'extension' => 'Module', 'name' => 'JobicMenu',
            'namespace' => JobicMenu::class, 'info' => ['multiple' => true],
        ]);
        $menu = Taxonomy::create(['type' => 'menu', 'status' => true]);
        $menu->translations()->create(['locale' => 'en', 'field' => 'name', 'value' => 'Main navigation']);
        $item = Taxonomy::create(['type' => 'menu_'.$menu->id, 'status' => true]);
        $child = Taxonomy::create(['type' => $item->type, 'parent_id' => $item->id, 'status' => true]);
        $child->translations()->create(['locale' => 'en', 'field' => 'name', 'value' => 'Nested link']);
        $url = route('admin.settings.extensions.plugins.store', $extension->id);
        $fields = ['menu_id' => $menu->id, 'orientation' => 'vertical', 'alignment' => 'center', 'show_title' => 'show', 'depth' => '1'];

        $result = $this->postJson($url, ['name' => 'Sidebar', 'status' => 1, 'fields' => $fields])->assertOk();
        $this->postJson($url, ['name' => 'Header', 'status' => 1, 'fields' => ['menu_id' => $menu->id]])->assertOk();
        $this->get($result->json('data.edit_url'))->assertOk()->assertSee('value="vertical" selected', false);

        $module = app(JobicMenu::class);
        $sidebar = $module->handle($extension->plugins()->findOrFail($result->json('data.id'))->fields->getArrayCopy());
        $header = $module->handle($extension->plugins()->where('name', 'Header')->sole()->fields->getArrayCopy());
        $this->assertStringContainsString('site-menu--vertical site-menu--center', $sidebar);
        $this->assertStringContainsString('class="site-menu__title">Main navigation', $sidebar);
        $this->assertStringNotContainsString('Nested link', $sidebar);
        $this->assertStringContainsString('Nested link', $header);
        $this->assertStringNotContainsString('site-menu__title', $header);
    }
}
