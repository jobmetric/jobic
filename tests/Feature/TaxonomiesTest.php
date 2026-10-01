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
        $this->get(route('admin.taxonomies.edit', ['categories', $id]))->assertOk()->assertSee('News')->assertSee(__('taxonomies.history'));
        $this->getJson(route('admin.taxonomies.history', ['categories', $id]))
            ->assertOk()->assertJsonPath('data.entries.0.operation', 'create');
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
        $this->getJson(route('admin.taxonomies.history', ['topics', $taxonomy->id]))
            ->assertOk()->assertJsonPath('data.entries.0.operation', 'update');
    }

    public function test_menu_is_a_general_taxonomy_and_registers_its_item_type(): void
    {
        $this->actingAs(User::factory()->create());
        $response = $this->postJson(route('admin.taxonomies.store', 'menu'), ['translation' => ['en' => ['name' => 'Header']], 'ordering' => 1, 'status' => 1])->assertCreated();
        $id = (int) $response->json('data.id');

        $this->assertDatabaseHas(config('taxonomy.tables.taxonomy'), ['id' => $id, 'type' => 'menu']);
        $this->assertTrue(TaxonomyTypeRegistry::has('menu_'.$id));
        $this->assertSame(route('admin.taxonomies.index', 'menu'), TaxonomyTypeRegistry::getOption('menu_'.$id, 'back-url'));
        $this->assertTrue(TaxonomyTypeRegistry::for('menu_'.$id)->hasBaseMedia());
        $this->getJson(route('admin.taxonomies.data', ['menu', 'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => 'Header'], 'order' => [['column' => 1, 'dir' => 'asc']]]))
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.name', 'Header');
        $item = $this->postJson(route('admin.taxonomies.store', 'menu_'.$id), ['translation' => ['en' => ['name' => 'Home']], 'ordering' => 1, 'status' => 1])->assertCreated();
        $this->postJson(route('admin.taxonomies.store', 'menu_'.$id), [
            'translation' => ['en' => ['name' => 'About']],
            'parent_id' => (int) $item->json('data.id'),
            'ordering' => 2,
            'status' => 1,
        ])->assertCreated();
        $this->assertDatabaseHas(config('taxonomy.tables.taxonomy'), ['type' => 'menu_'.$id]);
        $this->getJson(route('admin.taxonomies.data', ['menu_'.$id, 'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => 'About'], 'order' => [['column' => 2, 'dir' => 'asc']]]))
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.name', 'About')
            ->assertJsonPath('data.data.0.path_names', ['Home', 'About']);
        $this->getJson(route('admin.taxonomies.data', ['menu_'.$id, 'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => 'Home'], 'order' => [['column' => 2, 'dir' => 'asc']]]))
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.name', 'Home');
        $this->getJson(route('admin.taxonomies.data', ['menu_'.$id, 'draw' => 1, 'start' => 0, 'length' => 10, 'search' => ['value' => ''], 'order' => [['column' => 2, 'dir' => 'asc']]]))
            ->assertOk()->assertJsonPath('data.data.0.name', 'About')->assertJsonPath('data.data.1.name', 'Home');
        $this->get(route('admin.taxonomies.index', 'menu_'.$id))->assertOk()
            ->assertSee(route('admin.taxonomies.index', 'menu'), false)
            ->assertDontSee(__('taxonomies.created_at').'</th>', false);
    }

    public function test_menu_item_link_driver_is_hooked_validated_and_stored_as_metadata(): void
    {
        $this->actingAs(User::factory()->create());
        $menu = $this->postJson(route('admin.taxonomies.store', 'menu'), [
            'translation' => ['en' => ['name' => 'Footer']], 'ordering' => 0, 'status' => 1,
        ])->assertCreated();
        $type = 'menu_'.$menu->json('data.id');

        $this->get(route('admin.taxonomies.create', $type))->assertOk()
            ->assertSee(__('taxonomies.link_title'))->assertSee(__('taxonomies.link_driver_external'));
        $this->get(route('admin.taxonomies.link-fields', [$type, 'external']))->assertOk()->assertSee('menu_link_url', false);
        $this->postJson(route('admin.taxonomies.store', $type), [
            'translation' => ['en' => ['name' => 'Docs']], 'ordering' => 0, 'status' => 1,
            'menu_link_driver' => 'external', 'menu_link_url' => 'not-a-url',
        ])->assertUnprocessable()->assertJsonValidationErrors('menu_link_url');

        $created = $this->postJson(route('admin.taxonomies.store', $type), [
            'translation' => ['en' => ['name' => 'Docs']], 'ordering' => 0, 'status' => 1,
            'menu_link_driver' => 'external', 'menu_link_url' => 'https://example.com/docs',
        ])->assertCreated();
        $record = Taxonomy::query()->findOrFail($created->json('data.id'));
        $this->assertSame('external', $record->metas()->where('key', 'menu_link_driver')->value('value'));
        $this->assertSame(['menu_link_url' => 'https://example.com/docs'], json_decode((string) $record->metas()->where('key', 'menu_link_data')->value('value'), true));
    }

    public function test_menu_item_page_uses_request_locale_and_hierarchical_parent_options(): void
    {
        config()->set('installation.completed', false);
        config()->set('localization.bootstrap', [
            'en' => ['name' => 'English', 'locale' => 'en', 'direction' => 'ltr', 'flag' => null],
            'fa' => ['name' => 'فارسی', 'locale' => 'fa', 'direction' => 'rtl', 'flag' => null],
        ]);
        $this->actingAs(User::factory()->create(['ui_preferences' => ['locale' => 'fa']]));
        $menu = $this->postJson(route('admin.taxonomies.store', 'menu'), [
            'translation' => ['en' => ['name' => 'Header'], 'fa' => ['name' => 'سربرگ']], 'ordering' => 0, 'status' => 1,
        ])->assertCreated();
        $type = 'menu_'.$menu->json('data.id');
        $parent = $this->postJson(route('admin.taxonomies.store', $type), [
            'translation' => ['en' => ['name' => 'Products'], 'fa' => ['name' => 'محصولات']], 'ordering' => 0, 'status' => 1,
        ])->assertCreated();
        $this->postJson(route('admin.taxonomies.store', $type), [
            'translation' => ['en' => ['name' => 'Phones'], 'fa' => ['name' => 'گوشی‌ها']], 'parent_id' => $parent->json('data.id'), 'ordering' => 0, 'status' => 1,
        ])->assertCreated();

        $this->get(route('admin.taxonomies.index', $type))->assertOk()->assertSee('سربرگ');
        $this->get(route('admin.taxonomies.create', $type))->assertOk()->assertSee('— گوشی‌ها')
            ->assertSee(__('taxonomies.link_title'));
        $this->get(route('admin.users.index'))->assertOk()->assertSee('سربرگ')->assertDontSee('Header');
    }
}
