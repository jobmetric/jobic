<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JobMetric\Media\Enums\MediaTypeEnum;
use JobMetric\Media\Models\Media;
use JobMetric\Media\Models\MediaPath;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;
use Tests\TestCase;

/** Verify that every registry type can use the native Jobic taxonomy page. */
class TaxonomiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_taxonomy_detaches_media_without_deleting_the_file(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('media_public');
        TaxonomyTypeRegistry::register('usage_test')->label('Categories')->baseMedia();
        $media = $this->postJson(route('admin.media-manager.upload'), ['file' => UploadedFile::fake()->image('usage.png')])->assertCreated()->json('data.id');
        $id = $this->postJson(route('admin.taxonomies.store', 'usage_test'), [
            'translation' => ['en' => ['name' => 'News']], 'ordering' => 0, 'status' => 1,
            'media_selection' => ['base' => json_encode([$media])],
        ])->assertCreated()->json('data.id');
        $usages = app(\App\Services\Media\MediaManager::class)->usages(Media::findOrFail($media));
        $this->assertSame('Categories — News', $usages[0]['label']);
        $this->assertSame(__('taxonomies.media_base'), $usages[0]['collection']);
        $this->assertSame(route('admin.taxonomies.edit', ['usage_test', $id]), $usages[0]['url']);
        $this->deleteJson(route('admin.taxonomies.destroy', ['usage_test', $id]))->assertOk();
        $this->assertDatabaseMissing(config('media.tables.media_relation'), ['mediaable_type' => Taxonomy::class, 'mediaable_id' => $id]);
        $this->assertNotNull(Media::find($media));
        $this->assertSame([], app(\App\Services\Media\MediaManager::class)->usages(Media::findOrFail($media)));
    }

    public function test_root_name_can_match_an_existing_child_but_not_another_root(): void
    {
        $this->actingAs(User::factory()->create());
        TaxonomyTypeRegistry::register('root_scope')->hierarchical();
        $url = route('admin.taxonomies.store', 'root_scope');
        $payload = ['translation' => ['en' => ['name' => 'C']], 'ordering' => 0, 'status' => 1];
        $parent = $this->postJson($url, $payload)->assertCreated()->json('data.id');
        $payload['translation']['en']['name'] = 'A';
        $this->postJson($url, $payload + ['parent_id' => $parent])->assertCreated();
        $root = $this->postJson($url, $payload)->assertCreated()->json('data.id');
        $this->putJson(route('admin.taxonomies.update', ['root_scope', $root]), $payload)->assertOk();
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('translation.en.name');
    }

    public function test_names_are_unique_per_parent_and_validation_keeps_field_keys(): void
    {
        $this->actingAs(User::factory()->create());
        TaxonomyTypeRegistry::register('unique_tree')->hierarchical();
        $url = route('admin.taxonomies.store', 'unique_tree');
        $payload = ['translation' => ['en' => ['name' => 'Shared name']], 'ordering' => 0, 'status' => 1];
        $parent = $this->postJson($url, $payload)->assertCreated()->json('data.id');
        $this->postJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('translation.en.name');
        $childPayload = $payload + ['parent_id' => $parent];
        $child = $this->postJson($url, $childPayload)->assertCreated()->json('data.id');
        $this->postJson($url, $childPayload)->assertUnprocessable()->assertJsonValidationErrors('translation.en.name');
        $this->putJson(route('admin.taxonomies.update', ['unique_tree', $child]), $childPayload)->assertOk();
        $this->putJson(route('admin.taxonomies.update', ['unique_tree', $child]), $payload + ['parent_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('translation.en.name');
        $this->postJson($url, ['translation' => ['en' => ['name' => '']], 'ordering' => -1, 'status' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['translation.en.name', 'ordering', 'status']);
    }

    public function test_optional_fields_and_seo_use_the_installed_registry_options_api(): void
    {
        $this->actingAs(User::factory()->create());
        TaxonomyTypeRegistry::register('configured', [
            'label' => 'Configured category', 'seo' => true,
            'custom-fields' => ['subtitle' => ['label' => 'Subtitle', 'type' => 'text', 'rules' => ['required', 'string', 'max:30']]],
        ]);
        $this->get(route('admin.taxonomies.create', 'configured'))->assertOk()
            ->assertSee('custom_fields[subtitle]', false)->assertSee('seo[en][title]', false)
            ->assertSee('media_selection[seo_image]', false);
        $payload = ['translation' => ['en' => ['name' => 'Configured']], 'ordering' => 0, 'status' => 1];
        $this->postJson(route('admin.taxonomies.store', 'configured'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('custom_fields.subtitle');
        $payload += ['custom_fields' => ['subtitle' => 'Details'], 'seo' => ['en' => ['title' => 'Search title', 'index' => 1]]];
        $id = $this->postJson(route('admin.taxonomies.store', 'configured'), $payload)->assertCreated()->json('data.id');
        $this->get(route('admin.taxonomies.edit', ['configured', $id]))->assertOk()->assertSee('Details')->assertSee('Search title');
    }

    public function test_menu_item_edit_and_update_work_with_installed_package_in_both_locales(): void
    {
        config()->set('installation.completed', false);
        config()->set('localization.bootstrap', [
            'en' => ['name' => 'English', 'locale' => 'en', 'direction' => 'ltr', 'flag' => null],
            'fa' => ['name' => 'فارسی', 'locale' => 'fa', 'direction' => 'rtl', 'flag' => null],
        ]);
        foreach (['en', 'fa'] as $locale) {
            $this->actingAs(User::factory()->create(['ui_preferences' => ['locale' => $locale]]));
            $payload = ['translation' => ['en' => ['name' => 'Header '.$locale], 'fa' => ['name' => 'سربرگ '.$locale]], 'ordering' => 0, 'status' => 1];
            $menu = $this->postJson(route('admin.taxonomies.store', 'menu'), $payload)->assertCreated()->json('data.id');
            $type = 'menu_'.$menu;
            $this->get(route('admin.taxonomies.create', $type))->assertOk()->assertSee('data-taxonomy-form', false);
            $payload['menu_link_driver'] = 'external';
            $payload['menu_link_url'] = 'https://example.com/original';
            $id = $this->postJson(route('admin.taxonomies.store', $type), $payload)->assertCreated()->json('data.id');
            $this->get(route('admin.taxonomies.edit', [$type, $id]))->assertOk()
                ->assertSee('https://example.com/original')->assertSee('data-media-picker', false);
            $payload['menu_link_url'] = 'https://example.com/updated';
            $this->putJson(route('admin.taxonomies.update', [$type, $id]), $payload)->assertOk();
            $this->get(route('admin.taxonomies.edit', [$type, $id]))->assertOk()->assertSee('https://example.com/updated');
            $this->getJson(route('admin.taxonomies.history', [$type, $id]))->assertOk();
            $this->getJson(route('admin.taxonomies.data', [$type, 'draw' => 1, 'start' => 0, 'length' => 10]))
                ->assertOk()->assertJsonPath('data.recordsTotal', 1);
            $this->deleteJson(route('admin.taxonomies.destroy', [$type, $id]))->assertOk();
            $this->get(route('admin.taxonomies.edit', [$type, $id]))->assertNotFound();
        }
    }

    public function test_hierarchy_name_sort_keeps_parents_before_sorted_children(): void
    {
        TaxonomyTypeRegistry::register('sort_tree')->hierarchical();
        $this->actingAs(User::factory()->create());
        $create = function (string $name, ?int $parent = null): int {
            return $this->postJson(route('admin.taxonomies.store', 'sort_tree'), [
                'translation' => ['en' => ['name' => $name]], 'parent_id' => $parent, 'status' => 1, 'ordering' => 0,
            ])->assertCreated()->json('data.id');
        };
        $z = $create('Z parent'); $a = $create('A parent');
        $za = $create('A child', $z); $az = $create('Z child', $a); $aa = $create('A child', $a);
        $input = ['draw'=>1, 'start'=>0, 'length'=>10, 'order'=>[['column'=>1, 'dir'=>'asc']]];
        $response = $this->getJson(route('admin.taxonomies.data', ['sort_tree'] + $input))->assertOk();
        $this->assertSame([$a,$aa,$az,$z,$za], array_column($response->json('data.data'), 'id'));
        $input['start'] = 2;
        $response = $this->getJson(route('admin.taxonomies.data', ['sort_tree'] + $input))->assertOk();
        $this->assertSame([$az,$z,$za], array_column($response->json('data.data'), 'id'));
    }

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
            ->assertOk()->assertJsonPath('data.data.0.name', 'Home')->assertJsonPath('data.data.1.name', 'About');
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

    public function test_media_manager_browses_folders_searches_globally_and_exposes_workspace_controls(): void
    {
        $this->actingAs(User::factory()->create());
        TaxonomyTypeRegistry::register('media_picker')->label('Media picker')->baseMedia();
        $folder = Media::query()->create(['name' => 'catalogue', 'type' => MediaTypeEnum::FOLDER()]);
        MediaPath::query()->create(['media_id' => $folder->id, 'path_id' => $folder->id, 'level' => 0]);
        $file = Media::query()->create([
            'name' => 'hero', 'parent_id' => $folder->id, 'type' => MediaTypeEnum::FILE(), 'mime_type' => 'image/jpeg',
            'size' => 2048, 'content_id' => sha1('hero'), 'disk' => 'public', 'collection' => 'public',
            'uuid' => fake()->uuid(), 'extension' => 'jpg',
        ]);
        MediaPath::query()->insert([
            ['media_id' => $file->id, 'path_id' => $folder->id, 'level' => 0],
            ['media_id' => $file->id, 'path_id' => $file->id, 'level' => 1],
        ]);

        $this->get(route('admin.taxonomies.create', 'media_picker'))->assertOk()
            ->assertSee('data-fm-view="grid"', false)
            ->assertSee('data-fm-details', false)
            ->assertSee('data-fm-drop', false)
            ->assertSee('data-fm-uploads', false);
        $this->getJson(route('admin.media-manager.browse', ['parent_id' => $folder->id, 'sort' => 'name', 'direction' => 'asc']))
            ->assertOk()->assertJsonPath('data.0.id', $file->id)
            ->assertJsonPath('folder.id', $folder->id)
            ->assertJsonPath('breadcrumbs.1.name', 'catalogue');
        $this->getJson(route('admin.media-manager.browse', ['search' => 'hero', 'scope' => 'global']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $file->id);

        Storage::fake('media_public');
        $this->post(route('admin.media-manager.upload'), [
            'file' => UploadedFile::fake()->image('تصویر اصلی.png'),
            'parent_id' => $folder->id,
        ], ['Accept' => 'application/json'])->assertCreated()->assertJsonPath('ok', true);
        $this->assertDatabaseHas(config('media.tables.media'), ['parent_id' => $folder->id, 'type' => MediaTypeEnum::FILE()]);
    }
}
