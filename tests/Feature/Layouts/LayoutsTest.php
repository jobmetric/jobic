<?php

declare(strict_types=1);

namespace Tests\Feature\Layouts;

use App\Facades\Hooks;
use App\Models\User;
use App\Support\Layouts\PageTypeRegistry;
use App\Support\Layouts\ThemeManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use JobMetric\Extension\Models\Extension;
use JobMetric\Layout\Models\Layout;
use JobMetric\Rolix\Models\Role;
use Tests\TestCase;

/**
 * Verify the layout settings surface and page-type discovery contract.
 */
class LayoutsTest extends TestCase
{
    use RefreshDatabase;

    /** An authorized administrator can open the full-width CRUD inventory. */
    public function test_layout_inventory_uses_the_admin_crud_shell(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.layouts.index'))
            ->assertOk()
            ->assertSee(trans('layouts.title'))
            ->assertSee('data-layout-list', false)
            ->assertDontSee('cms-settings-links', false);
    }

    /** Layout creation stops before the editor when no valid active Theme exists. */
    public function test_layout_editor_requires_a_theme_with_positions(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create())
            ->get(route('admin.settings.layouts.create'))
            ->assertOk()
            ->assertSee('data-layout-theme-required', false)
            ->assertSee(trans('layouts.theme_required_title'))
            ->assertDontSee('data-layout-form', false)
            ->assertDontSee('cms-settings-links', false);
    }

    /** The layout inventory exposes the shared server-side table contract. */
    public function test_layout_inventory_data_uses_server_side_table_contract(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('admin.settings.layouts.data', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'search' => ['value' => ''],
                'order' => [['column' => 1, 'dir' => 'asc']],
            ]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.draw', 1)
            ->assertJsonStructure(['data' => ['recordsTotal', 'recordsFiltered', 'stats', 'data']]);
    }

    /** Bulk operations use the selected record version and return the shared response contract. */
    public function test_layout_bulk_status_operation_is_versioned(): void
    {
        $user = User::factory()->create();
        $layout = Layout::query()->create(['name' => 'Homepage', 'status' => true]);
        $row = $this->actingAs($user)->getJson(route('admin.settings.layouts.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
            'search' => ['value' => ''], 'order' => [['column' => 1, 'dir' => 'asc']],
        ]))->json('data.data.0');

        $this->postJson(route('admin.settings.layouts.bulk.deactivate'), [
            'entries' => [['id' => $layout->id, '_version' => $row['version']]],
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertFalse($layout->fresh()->status);
    }

    /** A permanent deletion preflights the selection and cleans up only owned records. */
    public function test_permanent_deletion_requires_permission_confirmation_and_trashed_versions(): void
    {
        $layout = Layout::create(['name' => 'Trash', 'status' => true]);
        $page = $layout->layoutPages()->create(['application' => 'test', 'page' => 'home']);
        $extension = Extension::create(['extension' => 'Module', 'name' => 'Fixture', 'namespace' => 'Tests\\Fixture', 'info' => []]);
        $plugin = $extension->plugins()->create(['name' => 'Reusable module', 'status' => true]);
        $placement = $layout->layoutPlugins()->create(['position' => 'top', 'ordering' => 0, 'plugin_id' => $plugin->id]);
        $active = Layout::create(['name' => 'Keep', 'status' => true]);
        $layout->delete();
        $url = route('admin.settings.layouts.bulk.force_delete');

        $restricted = User::factory()->create();
        $restricted->assignRole(Role::create([
            'type' => 'system', 'name' => 'Soft deletion only', 'allow' => ['admin.settings.layouts'],
            'deny' => ['admin.settings.layouts.force_delete'], 'is_super' => false,
        ])->id);
        $admin = User::factory()->create();
        $entries = $this->actingAs($admin)->getJson(route('admin.settings.layouts.data', [
            'draw' => 1, 'start' => 0, 'length' => 10,
        ]))->assertOk()->json('data.data');
        $entries = collect($entries)->mapWithKeys(fn (array $entry): array =>
            [$entry['id'] => ['id' => $entry['id'], '_version' => $entry['version']]])->all();
        $payload = ['entries' => [$entries[$layout->id]], 'confirm' => 1];

        $this->actingAs($restricted)->postJson($url, $payload)->assertForbidden();
        $this->actingAs($admin)->postJson($url, ['entries' => $payload['entries']])->assertUnprocessable()->assertJsonValidationErrors('confirm');
        $this->postJson($url, ['entries' => [$entries[$layout->id], $entries[$active->id]], 'confirm' => 1])->assertUnprocessable();
        $this->postJson($url, ['entries' => [['id' => $layout->id, '_version' => str_repeat('0', 64)]], 'confirm' => 1])->assertConflict();
        $this->assertSoftDeleted($layout);

        $events = [];
        $veto = true;
        Hooks::addAction('jobic.settings.layouts.saving', function (array $context) use (&$events, &$veto): void {
            $events[] = ['saving', $context];
            if ($veto) {
                throw ValidationException::withMessages(['entries' => 'Deletion vetoed.']);
            }
        });
        Hooks::addAction('jobic.settings.layouts.saved', function (array $context) use (&$events): void {
            $events[] = ['saved', $context];
        });

        $this->postJson($url, $payload)->assertUnprocessable();
        $this->assertSoftDeleted($layout);
        $this->assertSame(['saving'], array_column($events, 0));
        $events = [];
        $veto = false;
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('message', trans_choice('layouts.force_deleted', 1));
        $context = ['operation' => 'force_delete', 'layout_id' => $layout->id, 'user_id' => $admin->id];
        $this->assertSame([['saving', $context], ['saved', $context]], $events);
        $this->assertModelMissing($layout);
        $this->assertModelMissing($page);
        $this->assertModelMissing($placement);
        $this->assertModelExists($plugin);
        $this->assertModelExists($active);
    }

    /** Built-in layout page types are registered with translated labels. */
    public function test_builtin_page_types_are_discoverable(): void
    {
        $types = app(PageTypeRegistry::class);

        $this->assertTrue($types->has('home'));
        $this->assertSame(trans('layouts.page_types.home'), $types->translated()['home']);
    }

    /** Theme manifests expose one strict shared position map. */
    public function test_theme_manifest_rejects_nonstandard_json(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'jobic-layout-');
        file_put_contents($path, json_encode(['schema' => 1, 'positions' => ['top' => 'theme.top']]));
        $this->assertSame(['top' => 'theme.top'], app(ThemeManifest::class)->readFile($path)['positions']);

        file_put_contents($path, json_encode(['schema' => 1, 'positions' => ['Top Position' => 'theme.top']]));
        $this->assertNull(app(ThemeManifest::class)->readFile($path));
        unlink($path);
    }
}
