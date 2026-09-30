<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use Tests\TestCase;

/** Verify that every registry type can use the native Jobic taxonomy page. */
class TaxonomiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_type_has_shared_crud_page(): void
    {
        TaxonomyTypeRegistry::register('categories')->label('Categories')->hierarchical()->changeStatusInList();
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.taxonomies.index', 'categories'))->assertOk()->assertSee('Categories');
        $response = $this->postJson(route('admin.taxonomies.store', 'categories'), [
            'name' => 'News',
            'ordering' => 1,
            'status' => 1,
        ])->assertCreated();

        $id = $response->json('data.id');
        $this->assertDatabaseHas(config('taxonomy.tables.taxonomy'), ['id' => $id, 'type' => 'categories']);
        $this->get(route('admin.taxonomies.edit', ['categories', $id]))->assertOk()->assertSee('News');
    }

    public function test_unknown_type_is_rejected(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.taxonomies.index', 'unknown'))->assertNotFound();
    }

    public function test_menu_is_a_general_taxonomy_and_registers_its_item_type(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson(route('admin.taxonomies.store', 'menu'), ['name' => 'Header', 'ordering' => 1, 'status' => 1])->assertCreated();
        $id = (int) $response->json('data.id');

        $this->assertDatabaseHas(config('taxonomy.tables.taxonomy'), ['id' => $id, 'type' => 'menu']);
        $this->assertTrue(TaxonomyTypeRegistry::has('menu_'.$id));
        $this->postJson(route('admin.taxonomies.store', 'menu_'.$id), ['name' => 'Home', 'ordering' => 1, 'status' => 1])->assertCreated();
        $this->assertDatabaseHas(config('taxonomy.tables.taxonomy'), ['type' => 'menu_'.$id]);
    }
}
