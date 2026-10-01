<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;
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
            'translation' => ['en' => ['name' => 'News']],
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

    public function test_active_locale_name_is_required_and_all_active_translations_are_saved(): void
    {
        config()->set('installation.completed', false);
        config()->set('localization.bootstrap', [
            'en' => ['name' => 'English', 'locale' => 'en', 'direction' => 'ltr', 'flag' => null],
            'fa' => ['name' => 'فارسی', 'locale' => 'fa', 'direction' => 'rtl', 'flag' => null],
        ]);
        TaxonomyTypeRegistry::register('topics')->label('Topics');
        $this->actingAs(User::factory()->create(['ui_preferences' => ['locale' => 'fa']]));

        $this->postJson(route('admin.taxonomies.store', 'topics'), [
            'translation' => ['en' => ['name' => 'News']], 'ordering' => 0, 'status' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('translation.fa.name');

        $response = $this->postJson(route('admin.taxonomies.store', 'topics'), [
            'translation' => ['en' => ['name' => 'News'], 'fa' => ['name' => 'اخبار']],
            'ordering' => 0, 'status' => 1,
        ])->assertCreated();
        $taxonomy = Taxonomy::query()->findOrFail($response->json('data.id'));
        $this->assertSame('اخبار', $taxonomy->getTranslation('name', 'fa'));
        $this->assertSame('News', $taxonomy->getTranslation('name', 'en'));
    }

    public function test_translation_can_be_edited_from_the_list_for_one_language(): void
    {
        TaxonomyTypeRegistry::register('topics')->label('Topics');
        $this->actingAs(User::factory()->create());
        $created = $this->postJson(route('admin.taxonomies.store', 'topics'), [
            'translation' => ['en' => ['name' => 'News']], 'ordering' => 0, 'status' => 1,
        ])->assertCreated();

        $this->putJson(route('admin.taxonomies.translation', ['topics', $created->json('data.id'), 'fa']), [
            'fields' => ['name' => 'اخبار'],
        ])->assertOk()->assertJsonPath('data.locale', 'fa');

        $taxonomy = Taxonomy::query()->findOrFail($created->json('data.id'));
        $this->assertSame('News', $taxonomy->getTranslation('name', 'en'));
        $this->assertSame('اخبار', $taxonomy->getTranslation('name', 'fa'));
    }

    public function test_menu_is_a_general_taxonomy_and_registers_its_item_type(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson(route('admin.taxonomies.store', 'menu'), ['translation' => ['en' => ['name' => 'Header']], 'ordering' => 1, 'status' => 1])->assertCreated();
        $id = (int) $response->json('data.id');

        $this->assertDatabaseHas(config('taxonomy.tables.taxonomy'), ['id' => $id, 'type' => 'menu']);
        $this->assertTrue(TaxonomyTypeRegistry::has('menu_'.$id));
        $this->assertTrue(TaxonomyTypeRegistry::for('menu_'.$id)->hasBaseMedia());
        $this->getJson(route('admin.taxonomies.data', ['menu', 'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => 'Header'], 'order' => [['column' => 1, 'dir' => 'asc']]]))
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.name', 'Header');
        $this->postJson(route('admin.taxonomies.store', 'menu_'.$id), ['translation' => ['en' => ['name' => 'Home']], 'ordering' => 1, 'status' => 1])->assertCreated();
        $this->assertDatabaseHas(config('taxonomy.tables.taxonomy'), ['type' => 'menu_'.$id]);
    }
}
