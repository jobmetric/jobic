<?php

declare(strict_types=1);

namespace Tests\Feature\Layouts;

use App\Models\User;
use App\Support\Layouts\PageTypeRegistry;
use App\Support\Layouts\ThemeManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Layout\Models\Layout;
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
