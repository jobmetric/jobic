<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Support\RecordVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;
use Tests\TestCase;

/**
 * Verify the native menu manager against the taxonomy registry contract.
 */
class MenusTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Guests cannot read or mutate menu administration routes.
     */
    public function test_guests_cannot_access_menu_management(): void
    {
        $this->get(route('admin.menus.index'))->assertRedirect(route('login'));
        $this->postJson(route('admin.menus.store'), $this->values())->assertUnauthorized();
    }

    /**
     * Administrators can create, update, view, and delete menu taxonomies.
     */
    public function test_menu_crud_registers_dynamic_item_type(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->postJson(route('admin.menus.store'), $this->values())->assertCreated();
        $id = (int) $response->json('data.id');
        $menu = Taxonomy::query()->findOrFail($id);

        $this->assertSame('menu', $menu->type);
        $this->assertSame('Primary', $menu->getTranslation('name'));
        $this->assertTrue(TaxonomyTypeRegistry::has('menu_'.$id));
        $this->assertTrue(TaxonomyTypeRegistry::for('menu_'.$id)->hasHierarchical());

        $this->putJson(route('admin.menus.update', $id), $this->values([
            '_version' => RecordVersion::token($menu, 'menus'),
            'name' => 'Footer',
            'ordering' => 2,
        ]))->assertOk();

        $this->get(route('admin.menus.show', $id))->assertOk()->assertSee('data-readonly', false)->assertSee('Footer');
        $this->deleteJson(route('admin.menus.destroy', $id))->assertOk();
        $this->assertDatabaseMissing(config('taxonomy.tables.taxonomy'), ['id' => $id]);
        $this->assertFalse(TaxonomyTypeRegistry::has('menu_'.$id));
    }

    /**
     * Return a valid menu request payload.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function values(array $changes = []): array
    {
        return array_replace([
            'name' => 'Primary',
            'ordering' => 1,
            'status' => 1,
        ], $changes);
    }
}
