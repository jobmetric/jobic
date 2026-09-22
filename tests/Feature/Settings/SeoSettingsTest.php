<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Facades\Hooks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use JobMetric\Rolix\Models\Role;
use JobMetric\Setting\Facades\Setting;
use Tests\TestCase;

class SeoSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_page_uses_shared_settings_form_and_persists_package_settings(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $values = [
            'meta_title' => 'Jobic CMS',
            'meta_description' => 'A concise description for search results.',
            'meta_keywords' => 'cms, laravel, jobic',
            'robots_index' => true,
            'robots_follow' => false,
            'remove_open_graph_image' => false,
        ];

        $this->actingAs($user)
            ->get(route('admin.settings.edit', 'seo'))
            ->assertOk()
            ->assertSee('cms-settings-form')
            ->assertSee('data-seo-uploader', false)
            ->assertSee('data-seo-remove-image', false)
            ->assertSee(route('admin.settings.seo.update'), false)
            ->assertSee('name="meta_description"', false);

        $this->putJson(route('admin.settings.seo.update'), $values)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertHeader('X-CSRF-TOKEN');

        $this->assertSame('Jobic CMS', Setting::get('jobic_seo_meta_title'));
        $this->assertEquals(false, Setting::get('jobic_seo_robots_follow'));
        $this->assertDatabaseCount(config('setting.tables.setting'), 6);

        $this->get(route('admin.settings.edit', 'seo'))
            ->assertOk()
            ->assertSee('Jobic CMS')
            ->assertSee('A concise description for search results.');
    }

    public function test_seo_settings_are_validated_before_persistence(): void
    {
        $this->actingAs(User::factory()->create())
            ->putJson(route('admin.settings.seo.update'), [
                'meta_title' => str_repeat('a', 256),
                'meta_description' => str_repeat('a', 501),
                'robots_index' => 'invalid',
                'robots_follow' => true,
                'remove_open_graph_image' => false,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['meta_title', 'meta_description', 'robots_index']);

        $this->assertDatabaseCount(config('setting.tables.setting'), 0);
    }

    public function test_seo_hooks_run_around_persistence_without_exposing_values(): void
    {
        $events = [];
        $contexts = [];
        Hooks::addAction('jobic.settings.seo.saving', function (array $context) use (&$events, &$contexts): void {
            $events[] = 'saving';
            $contexts[] = $context;
            $this->assertFalse(Setting::exists('jobic_seo'));
        });
        Hooks::addAction('jobic.settings.seo.saved', function (array $context) use (&$events, &$contexts): void {
            $events[] = 'saved';
            $contexts[] = $context;
            $this->assertTrue(Setting::exists('jobic_seo'));
        });

        $user = User::factory()->create();
        $this->actingAs($user)->putJson(route('admin.settings.seo.update'), [
            'meta_title' => 'Private draft value',
            'meta_description' => '',
            'meta_keywords' => '',
            'robots_index' => true,
            'robots_follow' => true,
            'remove_open_graph_image' => false,
        ])->assertOk();

        $this->assertSame(['saving', 'saved'], $events);
        $this->assertSame($contexts[0], $contexts[1]);
        $this->assertSame(['section', 'user_id', 'fields'], array_keys($contexts[0]));
        $this->assertSame($user->id, $contexts[0]['user_id']);
        $this->assertStringNotContainsString('Private draft value', serialize($contexts));
    }

    public function test_seo_saving_hook_can_veto_persistence(): void
    {
        Hooks::addAction('jobic.settings.seo.saving', function (): void {
            throw ValidationException::withMessages(['meta_title' => 'Vetoed']);
        });

        $this->actingAs(User::factory()->create())->putJson(route('admin.settings.seo.update'), [
            'meta_title' => 'Rejected',
            'meta_description' => '',
            'meta_keywords' => '',
            'robots_index' => true,
            'robots_follow' => true,
            'remove_open_graph_image' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('meta_title');

        $this->assertFalse(Setting::exists('jobic_seo'));
    }

    public function test_seo_permission_independently_protects_the_menu_page_and_save(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'type' => 'system',
            'name' => 'SEO manager',
            'allow' => ['admin.settings.seo'],
            'deny' => [],
            'is_super' => false,
            'is_default' => false,
            'ordering' => 1,
        ]);
        $user->assignRole($role);

        $this->actingAs($user)->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee(route('admin.settings.edit', 'seo'), false)
            ->assertDontSee(route('admin.settings.edit', 'general'), false);
        $this->get(route('admin.settings.edit', 'seo'))->assertOk();
        $this->get(route('admin.settings.edit', 'general'))->assertForbidden();
        $this->putJson(route('admin.settings.seo.update'), [
            'meta_title' => '',
            'meta_description' => '',
            'meta_keywords' => '',
            'robots_index' => false,
            'robots_follow' => false,
            'remove_open_graph_image' => false,
        ])->assertOk();
    }

    public function test_open_graph_image_is_validated_stored_and_replaced(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());
        $values = [
            'meta_title' => 'Image preview',
            'meta_description' => '',
            'meta_keywords' => '',
            'robots_index' => true,
            'robots_follow' => true,
            'remove_open_graph_image' => false,
        ];

        $this->putJson(route('admin.settings.seo.update'), $values + [
            'open_graph_image' => UploadedFile::fake()->image('first.jpg', 1200, 630),
        ])->assertOk();
        $firstPath = Setting::get('jobic_seo_open_graph_image');
        Storage::disk('public')->assertExists($firstPath);

        $this->putJson(route('admin.settings.seo.update'), $values + [
            'open_graph_image' => UploadedFile::fake()->image('second.png', 1200, 630),
        ])->assertOk();
        $secondPath = Setting::get('jobic_seo_open_graph_image');
        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);

        $this->putJson(route('admin.settings.seo.update'), $values + [
            'open_graph_image' => UploadedFile::fake()->create('invalid.svg', 10, 'image/svg+xml'),
        ])->assertUnprocessable()->assertJsonValidationErrors('open_graph_image');

        $this->putJson(route('admin.settings.seo.update'), array_replace($values, [
            'remove_open_graph_image' => true,
        ]))->assertOk();
        $this->assertNull(Setting::get('jobic_seo_open_graph_image'));
        Storage::disk('public')->assertMissing($secondPath);
    }
}
