<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Facades\Hooks;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;
use JobMetric\Rolix\Models\Role;
use JobMetric\Setting\Contracts\AbstractSetting;
use JobMetric\Setting\Facades\Setting;
use JobMetric\Setting\Facades\SettingNamespaceRegistry;
use JobMetric\Setting\Facades\SettingRegistry;
use Tests\Fixtures\DynamicSetting;
use Tests\TestCase;

/** Verify discovery, extension, authorization, and secure dynamic persistence. */
class DynamicSettingsTest extends TestCase
{
    use RefreshDatabase;

    /** Register the fixture in the package registry before each request. */
    protected function setUp(): void
    {
        parent::setUp();
        SettingNamespaceRegistry::register('Tests\\Fixtures');
        self::assertTrue(SettingNamespaceRegistry::has('Tests\\Fixtures'));
        self::assertTrue(is_subclass_of(DynamicSetting::class, AbstractSetting::class));
        SettingRegistry::discover();
        self::assertTrue(SettingRegistry::has(DynamicSetting::class));
    }

    /** Render and save a discovered form without accepting unknown input. */
    public function test_discovered_form_is_rendered_and_persisted(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $url = route('admin.settings.forms.edit', ['test', 'dynamic']);

        $this->actingAs($user)->get($url)->assertOk()->assertSee('name="label"', false)->assertSee('name="secret"', false);
        $this->putJson($url, ['label' => 'Stored', 'secret' => 'private-value', 'unknown' => 'discarded'])
            ->assertOk()->assertJsonPath('ok', true)->assertHeader('X-CSRF-TOKEN');

        $this->assertSame('Stored', Setting::get('test_dynamic_label'));
        $this->assertSame('private-value', Setting::get('test_dynamic_secret'));
        $this->assertFalse(Setting::has('test_dynamic_unknown'));
        $this->assertStringStartsWith('__encrypted__:', Setting::form('test_dynamic')['test_dynamic_secret']);
    }

    /** Include fields added by the CMS builder filter in validation and storage. */
    public function test_builder_hook_fields_share_the_render_validation_and_save_definition(): void
    {
        Hooks::addFilter('jobic.admin.settings.form.builder', function (FormBuilder $builder): FormBuilder {
            return $builder->tab(function (TabBuilder $tab): void {
                $tab->id('extension')->label('admin.settings')->customField(function (CustomFieldBuilder $field): void {
                    $field::text()->name('extension')->label('admin.settings')->validation('required|string');
                });
            });
        });
        $url = route('admin.settings.forms.update', ['test', 'dynamic']);
        $this->actingAs(User::factory()->create())->putJson($url, ['label' => 'Base'])->assertUnprocessable()->assertJsonValidationErrors('extension');
        $this->putJson($url, ['label' => 'Base', 'extension' => 'Added'])->assertOk();
        $this->assertSame('Added', Setting::get('test_dynamic_extension'));
    }

    /** Exclude sensitive values and field names from lifecycle hook contexts. */
    public function test_lifecycle_hooks_exclude_sensitive_input(): void
    {
        $contexts = [];
        Hooks::addAction('jobic.settings.dynamic.saving', function (array $context) use (&$contexts): void {
            $contexts[] = $context;
        });
        Hooks::addAction('jobic.settings.dynamic.saved', function (array $context) use (&$contexts): void {
            $contexts[] = $context;
        });
        $this->actingAs(User::factory()->create())->putJson(route('admin.settings.forms.update', ['test', 'dynamic']), [
            'label' => 'Public', 'secret' => 'never-expose',
        ])->assertOk();

        $this->assertCount(2, $contexts);
        $this->assertSame(['label'], $contexts[0]['fields']);
        $this->assertStringNotContainsString('never-expose', serialize($contexts));
    }

    /** Enforce the generated permission on pages, saves, and settings navigation. */
    public function test_each_discovered_form_has_an_independent_permission(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['type' => 'system', 'name' => 'Dynamic settings manager',
            'allow' => ['admin.settings.test.dynamic'], 'deny' => [], 'is_super' => false,
            'is_default' => false, 'ordering' => 1]);
        $user->assignRole($role);
        $url = route('admin.settings.forms.edit', ['test', 'dynamic']);

        $this->actingAs($user)->get(route('admin.settings.index'))->assertOk()->assertSee($url, false);
        $this->get($url)->assertOk();
        $this->putJson($url, ['label' => 'Allowed'])->assertOk();

        $denied = User::factory()->create();
        $deniedRole = Role::query()->create(['type' => 'system', 'name' => 'Denied dynamic settings',
            'allow' => [], 'deny' => ['admin.settings.test.dynamic'], 'is_super' => false,
            'is_default' => false, 'ordering' => 2]);
        $denied->assignRole($deniedRole);
        $this->actingAs($denied)->get($url)->assertForbidden();
        $this->putJson($url, ['label' => 'Rejected'])->assertForbidden();
    }
}
