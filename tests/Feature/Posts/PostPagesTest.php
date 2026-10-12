<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Facades\AdminMenu;
use App\Models\User;
use App\Services\Posts\PostFlowProvisioner;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JobMetric\Flow\Models\Flow;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Domi\Facades\Domi;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use Tests\TestCase;

class PostPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_page_and_post_taxonomy_screens_render(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        foreach ([
            route('admin.posts.index', ['type' => 'post']),
            route('admin.posts.create', ['type' => 'post']),
            route('admin.posts.index', ['type' => 'page']),
            route('admin.posts.create', ['type' => 'page']),
            route('admin.taxonomies.index', ['type' => 'category']),
            route('admin.taxonomies.index', ['type' => 'tag']),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_post_password_can_be_set_preserved_and_explicitly_removed(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        foreach (['post', 'page'] as $type) {
            $this->get(route('admin.posts.create', ['type' => $type]))
                ->assertOk()
                ->assertSee('name="password"', false);
        }

        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']), [
            'status' => 'draft',
            'slug' => 'protected-post',
            'password' => 'content-secret',
            'translation' => ['en' => ['title' => 'Protected post']],
        ])->assertOk();
        $id = $created->json('data.id');

        $post = Post::findOrFail($id);
        $this->assertSame('content-secret', $post->password);

        $editUrl = route('admin.posts.edit', ['type' => 'post', 'post' => $id]);
        $form = $this->get($editUrl)->assertOk();
        $form->assertSee('name="clear_password"', false)
            ->assertDontSee('content-secret');

        $updateUrl = route('admin.posts.update', ['type' => 'post', 'post' => $id]);
        $payload = [
            'status' => 'draft',
            'slug' => 'protected-post',
            'password' => '',
            '_version' => $created->json('data.version'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'translation' => ['en' => ['title' => 'Protected post']],
        ];
        $updated = $this->putJson($updateUrl, $payload)->assertOk();
        $this->assertSame('content-secret', $post->fresh()->password);

        $payload['_version'] = $updated->json('data.version');
        $payload['clear_password'] = true;
        $this->putJson($updateUrl, $payload)->assertOk();
        $this->assertNull($post->fresh()->password);
    }

    public function test_posts_and_pages_have_distinct_permissions_and_a_shared_level_two_unlock_permission(): void
    {
        $this->assertSame('admin.content.posts.view', PostTypeRegistry::for('post')->getViewPermission());
        $this->assertSame('admin.content.posts.manage', PostTypeRegistry::for('post')->getManagePermission());
        $this->assertSame('admin.content.pages.view', PostTypeRegistry::for('page')->getViewPermission());
        $this->assertSame('admin.content.pages.manage', PostTypeRegistry::for('page')->getManagePermission());

        $permissions = app(PermissionRegistry::class);
        $this->assertTrue($permissions->has('admin.unlock_edit_lock'));
        $this->assertFalse($permissions->has('admin.taxonomies.unlock'));
    }

    public function test_each_post_taxonomy_has_its_own_permission_keys(): void
    {
        $permissions = app(PermissionRegistry::class);

        foreach (['category', 'tag'] as $type) {
            $builder = TaxonomyTypeRegistry::for($type);
            $base = 'admin.content.taxonomy_' . $type;

            $this->assertSame($base, $builder->getPermission());
            $this->assertSame($base . '.view', $builder->getViewPermission());
            $this->assertSame($base . '.manage', $builder->getManagePermission());
            $this->assertTrue($permissions->has($base));
            $this->assertTrue($permissions->has($base . '.view'));
            $this->assertTrue($permissions->has($base . '.manage'));
        }

        $this->assertFalse($permissions->has('admin.taxonomies'));
        $this->assertFalse($permissions->has('admin.menus'));
        $this->assertFalse($permissions->has('admin.posts'));
        $this->assertFalse($permissions->has('admin.pages'));
        $this->assertTrue($permissions->has('admin.content.posts.view'));
        $this->assertTrue($permissions->has('admin.content.pages.view'));
    }

    public function test_posts_and_pages_use_the_shared_edit_lock_release_permission(): void
    {
        $this->actingAs(User::factory()->create());
        foreach (['post', 'page'] as $type) {
            $record = Post::query()->create(['type' => $type, 'status' => 'draft']);
            $record->translate('en', ['title' => ucfirst($type).' lock test']);
            $editUrl = route('admin.posts.edit', ['type' => $type, 'post' => $record->id]);

            $this->get($editUrl)->assertOk();
            $this->actingAs(User::factory()->create());
            $this->get($editUrl)->assertRedirect(route('admin.posts.index', ['type' => $type]));
            $this->postJson(route('admin.posts.edit-lock.unlock', ['type' => $type, 'post' => $record->id]))->assertOk();
            $this->get($editUrl)->assertOk();
        }
    }

    public function test_revision_history_shows_readable_safe_snapshots_instead_of_raw_json(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'publish']);
        $post->translate('fa', ['title' => 'عنوان فعلی']);
        $post->revisions()->create(['snapshot' => [
            'attributes' => ['status' => 'draft', 'password' => 'private-value', 'slug' => 'old-post'],
            'translation' => [
                ['locale' => 'fa', 'field' => 'title', 'value' => 'عنوان نسخه قدیمی'],
                ['locale' => 'fa', 'field' => 'excerpt', 'value' => 'خلاصه نسخه قدیمی'],
                ['locale' => 'fa', 'field' => 'content', 'value' => json_encode(['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'متن نسخه']]]])],
            ],
            'metadata' => ['secret' => 'hidden-value'],
            'files' => [],
            'taxonomies' => [],
        ]]);

        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $post->id]))
            ->assertOk()
            ->assertSee('post-revisions__list', false)
            ->assertSee('crud-help-section post-revisions__item', false)
            ->assertSee('عنوان نسخه قدیمی')
            ->assertSee('خلاصه نسخه قدیمی')
            ->assertSee('old-post')
            ->assertSee('data-revision-restore', false)
            ->assertDontSee('private-value')
            ->assertDontSee('hidden-value')
            ->assertDontSee('متن نسخه');
    }

    public function test_empty_post_list_returns_an_empty_data_set_and_page_uses_its_own_create_label(): void
    {
        $this->withoutVite();
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());

        $this->getJson(route('admin.posts.data', ['type' => 'post']))
            ->assertOk()->assertJsonPath('data.recordsTotal', 0)->assertJsonPath('data.data', []);
        $this->get(route('admin.posts.index', ['type' => 'page']))
            ->assertOk()->assertSee('data-table-empty', false);
        $this->get(route('admin.posts.create', ['type' => 'page']))
            ->assertOk()->assertSee('Add page');
    }

    public function test_post_and_page_lists_use_concise_localized_descriptions(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        app()->setLocale('en');
        $this->get(route('admin.posts.index', ['type' => 'post']))
            ->assertOk()->assertSee('articles and news');
        $this->get(route('admin.posts.index', ['type' => 'page']))
            ->assertOk()->assertSee('permanent website pages');

        $this->actingAs(User::factory()->create(['ui_preferences' => ['locale' => 'fa']]));
        $this->get(route('admin.posts.index', ['type' => 'post']))
            ->assertOk()->assertSee('مقاله‌ها و خبرها');
        $this->get(route('admin.posts.index', ['type' => 'page']))
            ->assertOk()->assertSee('برگه‌های ثابت وب‌سایت');
    }

    public function test_post_and_page_lists_receive_their_own_localized_accordion_help(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        app()->setLocale('fa');

        $this->get(route('admin.posts.index', ['type' => 'post']))->assertOk();
        $postHelp = Domi::localize()['postPage'];
        $this->assertSame(trans('posts.help_title'), $postHelp['helpTitle']);
        $this->assertSame(trans('posts.help_intro'), $postHelp['helpIntro']);
        $this->assertSame(trans('posts.help_items'), $postHelp['help']);
        $this->assertSame(['activate', 'deactivate', 'delete', 'help', 'unlock'], $postHelp['operations']);
        $this->assertSame('آزادسازی قفل ویرایش', trans('posts.unlock', locale: 'fa'));
        $this->assertSame('Release edit locks', trans('posts.unlock', locale: 'en'));

        $this->get(route('admin.posts.index', ['type' => 'page']))->assertOk();
        $pageHelp = Domi::localize()['postPage'];
        $this->assertSame(trans('posts.page_help_title'), $pageHelp['helpTitle']);
        $this->assertSame(trans('posts.page_help_intro'), $pageHelp['helpIntro']);
        $this->assertSame(trans('posts.page_help_items'), $pageHelp['help']);
        $this->assertNotSame($postHelp['help'], $pageHelp['help']);
    }

    public function test_taxonomy_filters_limit_posts_to_the_selected_term(): void
    {
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());
        $termId = (int) $this->postJson(route('admin.taxonomies.store', ['type' => 'category']), [
            'translation' => ['en' => ['name' => 'News']], 'ordering' => 0, 'status' => 1,
        ])->assertCreated()->json('data.id');
        $matching = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $matching->translate('en', ['title' => 'Matching']);
        $matching->attachTaxonomy($termId, 'categories');
        $other = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $other->translate('en', ['title' => 'Other']);

        $this->getJson(route('admin.posts.data', ['type' => 'post']).'?filters%5Btaxonomy.category%5D='.$termId)
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.id', $matching->id);
    }

    public function test_external_post_type_can_register_a_custom_list_filter(): void
    {
        $this->actingAs(User::factory()->create());
        PostTypeRegistry::register('note')->translationFields(['title'])->addFilter('visibility', [
            'label' => 'Visibility', 'options' => ['draft' => 'Draft'],
            'apply' => static fn ($query, $value) => $query->where('status', $value),
        ]);
        Post::query()->create(['type' => 'note', 'status' => 'draft']);
        Post::query()->create(['type' => 'note', 'status' => 'publish']);

        $this->getJson(route('admin.posts.data', ['type' => 'note']).'?filters%5Bvisibility%5D=draft')
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1);
    }

    public function test_post_form_only_contains_the_current_locale_translation_fields(): void
    {
        $this->withoutVite();
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.posts.create', ['type' => 'post']))
            ->assertOk()->assertSee('translation[en][title]', false)
            ->assertSee('<template data-record-history-template>', false)
            ->assertDontSee('translation[fa][title]', false);
    }

    public function test_has_seo_shares_one_media_card_and_stores_global_index_and_image(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        Storage::fake('media_public');

        $form = $this->get(route('admin.posts.create', ['type' => 'post']))->assertOk();
        $this->assertSame(1, substr_count($form->getContent(), 'class="crud-capability-card post-media-field"'));
        $form->assertSee('media_selection[seo_image]', false)
            ->assertSee('metadata[seo_index]', false)
            ->assertDontSee('translation[en][seo_index]', false);

        $mediaId = $this->postJson(route('admin.media-manager.upload'), [
            'file' => UploadedFile::fake()->image('post-sharing.png'),
        ])->assertCreated()->json('data.id');
        $id = $this->postJson(route('admin.posts.store', ['type' => 'post']), [
            'status' => 'draft',
            'slug' => 'seo-post',
            'translation' => ['en' => ['title' => 'SEO post', 'meta_title' => 'Search result title']],
            'metadata' => ['seo_index' => 0],
            'media_selection' => ['seo_image' => json_encode([$mediaId])],
        ])->assertOk()->json('data.id');

        $post = Post::findOrFail($id);
        $this->assertSame('0', (string) $post->getMetadata()->get('seo_index'));
        $this->assertSame([(int) $mediaId], $post->getMediaByCollection('seo_image')->get()->pluck('id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_package_service_preserves_shared_media_collections_and_previous_revision(): void
    {
        $this->actingAs(User::factory()->create());
        Storage::fake('media_public');
        $mediaId = (int) $this->postJson(route('admin.media-manager.upload'), [
            'file' => UploadedFile::fake()->image('shared-collection.png'),
        ])->assertCreated()->json('data.id');

        PostTypeRegistry::register('article')->baseMedia()->mediaCollection('gallery', true);
        $service = app(\JobMetric\Post\Post::class);
        $post = $service->store('article', [
            'status' => 'draft',
            'translation' => ['en' => ['title' => 'Before']],
            'media' => ['base' => [$mediaId], 'gallery' => [$mediaId]],
        ])->data['post'];

        $service->update('article', (int) $post->id, [
            'status' => 'publish',
            'translation' => ['en' => ['title' => 'After']],
            'media' => ['gallery' => []],
        ]);
        $post->refresh();
        $this->assertSame([$mediaId], $post->files()->wherePivot('collection', 'base')->pluck('media.id')->all());
        $this->assertCount(0, $post->files()->wherePivot('collection', 'gallery')->get());
        $revision = $post->revisions()->oldest('id')->firstOrFail();
        $this->assertSame('draft', $revision->snapshot['attributes']['status']);
        $this->assertSame('Before', collect($revision->snapshot['translation'])->firstWhere('field', 'title')['value']);
        $post->restoreRevision((int) $revision->id);
        $this->assertSame('Before', $post->fresh()->getTranslation('title', 'en'));
        $this->assertSame('publish', $post->fresh()->status);
        $this->assertSame([$mediaId], $post->files()->wherePivot('collection', 'gallery')->pluck('media.id')->all());
    }

    public function test_package_service_rejects_unknown_taxonomy_without_partial_writes(): void
    {
        PostTypeRegistry::register('article')->translationFields(['title']);
        $service = app(\JobMetric\Post\Post::class);
        $post = $service->store('article', ['translation' => ['en' => ['title' => 'Before']]])->data['post'];
        try {
            $service->update('article', (int) $post->id, [
                'translation' => ['en' => ['title' => 'After']],
                'taxonomies' => ['unknown' => [123]],
            ]);
            $this->fail('An unregistered collection must be rejected.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('taxonomies', $exception->errors());
        }
        $this->assertSame('Before', $post->fresh()->getTranslation('title', 'en'));
    }

    public function test_persian_publish_date_is_converted_before_storage(): void
    {
        $this->actingAs(User::factory()->create(['ui_preferences' => ['locale' => 'fa']]));
        $jalali = \JobMetric\MultiCalendar\Factory\CalendarConverterFactory::make('jalali')->fromGregorian(2026, 10, 8, '/');

        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']), [
            'status' => 'draft', 'slug' => 'persian-publish-date',
            'published_at' => $jalali.' 13:45',
            'translation' => ['fa' => ['title' => 'نوشته آزمایشی']],
        ])->assertOk();

        $this->assertSame('2026-10-08 13:45', Post::query()->findOrFail($created->json('data.id'))->published_at?->format('Y-m-d H:i'));
    }

    public function test_content_menu_has_the_requested_post_and_menu_hierarchy(): void
    {
        app()->setLocale('fa');
        $this->actingAs(User::factory()->create());

        $items = AdminMenu::items('content');
        $this->assertSame(
            ['posts', 'pages', 'manage-menus'],
            array_column(array_slice($items, 0, 3), 'key')
        );
        $this->assertSame(
            ['all-posts', 'category', 'tag'],
            array_column($items[0]['children'], 'key')
        );
        $this->assertSame('همه نوشته‌ها', $items[0]['children'][0]['label']);
        $this->assertSame(route('admin.posts.index', ['type' => 'post']), $items[0]['children'][0]['url']);
    }

    public function test_post_bulk_operation_requires_a_valid_bounded_selection(): void
    {
        $this->actingAs(User::factory()->create());

        $this->postJson(route('admin.posts.bulk', ['type' => 'post']), [
            'operation' => 'publish-everything',
            'ids' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['operation', 'ids']);
    }

    public function test_bulk_publish_runs_the_registered_post_flow(): void
    {
        $this->actingAs(User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);

        $this->postJson(route('admin.posts.bulk', ['type' => 'post']), [
            'operation' => 'activate', 'ids' => [$post->id],
        ])->assertOk();

        $this->assertSame('publish', $post->fresh()->status);
        $this->assertDatabaseHas(config('workflow.tables.flow_instance', 'flow_instances'), [
            'instanceable_type' => Post::class,
            'instanceable_id' => $post->id,
        ]);

        $this->postJson(route('admin.posts.bulk', ['type' => 'post']), [
            'operation' => 'deactivate', 'ids' => [$post->id],
        ])->assertOk();
        $this->assertSame('disabled', $post->fresh()->status);
    }

    public function test_starter_workflow_provisioning_is_idempotent(): void
    {
        $flow = Flow::query()->where('subject_type', Post::class)->where('subject_collection', 'post')->firstOrFail();
        $states = $flow->states()->count();
        $transitions = $flow->transitions()->count();
        $pending = $flow->states()->where('status', 'pending')->firstOrFail();
        $pending->translate('en', ['name' => 'Custom review step']);

        app(PostFlowProvisioner::class)->ensureDefaults();

        $this->assertSame($states, $flow->states()->count());
        $this->assertSame($transitions, $flow->transitions()->count());
        $this->assertSame('Custom review step', $pending->fresh()->getTranslation('name', 'en'));
    }

    public function test_post_screens_use_the_shared_taxonomy_crud_structure(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.posts.index', ['type' => 'post']))
            ->assertOk()
            ->assertSee('data-select-all', false)
            ->assertSee('data-show-selected', false)
            ->assertSee('data-filter-form', false)
            ->assertSee('data-column="translations"', false)
            ->assertSee('data-column="media"', false);

        foreach (['category', 'tag'] as $taxonomyType) {
            $this->postJson(route('admin.taxonomies.store', ['type' => $taxonomyType]), [
                'translation' => ['en' => ['name' => 'Sample '.$taxonomyType]],
                'ordering' => 0,
                'status' => 1,
            ])->assertCreated();
        }

        $this->get(route('admin.posts.create', ['type' => 'post']))
            ->assertOk()
            ->assertSee('crud-taxonomy-layout has-sidebar', false)
            ->assertSee('crud-translation-group post-translation-group', false)
            ->assertSee('data-locale-panel="en"', false)
            ->assertDontSee('data-locale-tab', false)
            ->assertSee('post-media-field', false)
            ->assertSee('post-url-field', false)
            ->assertSee('data-taxonomy-quick-add', false)
            ->assertSee('type="checkbox" name="taxonomies[categories][]"', false)
            ->assertSee('type="checkbox" name="taxonomies[tags][]"', false)
            ->assertSee('data-taxonomy-parent', false)
            ->assertSee('data-taxonomy-dialog', false)
            ->assertSee('data-post-datepicker', false);
    }

    /**
     * Render a single-choice taxonomy as radios without a hierarchical parent control.
     *
     * @return void
     */
    public function test_post_taxonomy_choice_mode_follows_the_post_type_registry(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        PostTypeRegistry::register('single-choice')
            ->label('Single choice')
            ->translationFields(['title'])
            ->taxonomy('tag', 'topics', false)
            ->workflow('post');
        $this->postJson(route('admin.taxonomies.store', ['type' => 'tag']), [
            'translation' => ['en' => ['name' => 'One topic']],
            'ordering' => 0,
            'status' => 1,
        ])->assertCreated();

        $this->get(route('admin.posts.create', ['type' => 'single-choice']))
            ->assertOk()
            ->assertSee('type="radio" name="taxonomies[topics][]"', false)
            ->assertDontSee('data-taxonomy-parent', false);
    }

    public function test_registered_post_type_without_media_does_not_gain_an_implicit_media_field(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
        PostTypeRegistry::register('note')->label('Notes')->translationFields(['title', 'content']);

        $this->get(route('admin.posts.index', ['type' => 'note']))
            ->assertOk()->assertDontSee('data-column="media"', false);
        $this->get(route('admin.posts.create', ['type' => 'note']))
            ->assertOk()->assertDontSee('post-media-field', false);

        $record = Post::query()->create(['type' => 'note', 'status' => 'draft']);
        $this->putJson(route('admin.posts.update', ['type' => 'note', 'post' => $record->id]).'?locale=en', [
            '_version' => \App\Support\RecordVersion::token($record->fresh(), 'posts:note'),
            'status' => 'publish',
            'translation' => ['en' => ['title' => 'A small note']],
        ])->assertOk();
        $this->assertSame('publish', $record->fresh()->status);
    }

    public function test_registered_post_type_can_reuse_a_named_workflow(): void
    {
        PostTypeRegistry::register('article')->translationFields(['title'])->workflow('post');
        $record = Post::query()->create(['type' => 'article', 'status' => 'draft']);

        $this->assertSame('post', $record->boundFlow()?->subject_collection);
    }

    public function test_post_form_shows_hierarchical_taxonomy_options_in_tree_order(): void
    {
        $this->withoutVite();
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());

        $parent = $this->postJson(route('admin.taxonomies.store', ['type' => 'category']), [
            'translation' => ['en' => ['name' => 'Parent']],
            'ordering' => 0,
            'status' => 1,
        ])->assertCreated()->json('data.id');

        $this->postJson(route('admin.taxonomies.store', ['type' => 'category']), [
            'translation' => ['en' => ['name' => 'Child']],
            'parent_id' => $parent,
            'ordering' => 0,
            'status' => 1,
        ])->assertCreated();

        $this->get(route('admin.posts.create', ['type' => 'post']))
            ->assertOk()
            ->assertSee('Parent', false)
            ->assertSee('— Child', false);
    }

    public function test_hierarchical_taxonomy_filters_prefix_children_with_dashes(): void
    {
        $this->withoutVite();
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());

        $parent = (int) $this->postJson(route('admin.taxonomies.store', ['type' => 'category']), [
            'translation' => ['en' => ['name' => 'Filter parent']],
            'ordering' => 0,
            'status' => 1,
        ])->assertCreated()->json('data.id');

        $this->postJson(route('admin.taxonomies.store', ['type' => 'category']), [
            'translation' => ['en' => ['name' => 'Filter child']],
            'parent_id' => $parent,
            'ordering' => 0,
            'status' => 1,
        ])->assertCreated();

        $this->get(route('admin.posts.index', ['type' => 'post']))
            ->assertOk()
            ->assertSee('>Filter parent</option>', false)
            ->assertSee('>— Filter child</option>', false);
    }

    public function test_list_translation_dialog_reads_and_saves_one_locale_without_changing_another(): void
    {
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $post->translate('en', ['title' => 'Original title']);
        $post->translate('fa', ['title' => 'عنوان فارسی']);

        $translation = $this->getJson(route('admin.posts.translation', ['type' => 'post', 'post' => $post->id, 'locale' => 'en']))
            ->assertOk()->assertJsonPath('data.fields.title', 'Original title');

        $this->putJson(route('admin.posts.translation.update', ['type' => 'post', 'post' => $post->id, 'locale' => 'en']), [
            '_version' => $translation->json('data.version'),
            'fields' => ['title' => 'Updated title', 'excerpt' => 'Short summary', 'content' => json_encode(['blocks' => []])],
        ])->assertOk()->assertJsonPath('data.fields.title', 'Updated title');

        $post->refresh();
        $this->assertSame('Updated title', $post->getTranslation('title', 'en'));
        $this->assertSame('عنوان فارسی', $post->getTranslation('title', 'fa'));
        $this->assertSame('Short summary', $post->getTranslation('excerpt', 'en'));
        $this->assertTrue($post->revisions()->exists());
    }

    public function test_post_save_and_shared_history_record_the_changes(): void
    {
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());

        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=en', [
            'status' => 'draft',
            'slug' => 'post-history-test',
            'translation' => ['en' => ['title' => 'Original post', 'content' => json_encode(['blocks' => [
                ['id' => 'intro', 'type' => 'paragraph', 'data' => ['text' => 'Saved editor content']],
            ]])]],
        ])->assertOk();
        $id = (int) $created->json('data.id');
        $savedPost = Post::query()->findOrFail($id);
        $this->assertNotNull($savedPost->boundFlow());
        $this->assertNull($savedPost->getTranslation('content', 'en'));
        $this->assertSame('Saved editor content', json_decode(app(\App\Services\Editor\EditorDocuments::class)->read($savedPost, 'en'), true)['blocks'][0]['data']['text']);
        $this->assertDatabaseHas(config('workflow.tables.flow_instance', 'flow_instances'), [
            'instanceable_type' => Post::class,
            'instanceable_id' => $id,
        ]);

        $this->getJson(route('admin.posts.data', ['type' => 'post']))
            ->assertOk()
            ->assertJsonPath('data.data.0.title', 'Original post');

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            '_version' => $created->json('data.version'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'status' => 'publish',
            'slug' => 'post-history-test',
            'translation' => ['en' => ['title' => 'Updated post', 'content' => json_encode(['blocks' => [
                ['id' => 'updated', 'type' => 'paragraph', 'data' => ['text' => 'Updated editor content']],
            ]])]],
        ])->assertOk();

        $history = $this->getJson(route('admin.posts.history', ['type' => 'post', 'post' => $id]))->assertOk();
        $this->assertSame('update', $history->json('data.entries.0.operation'));
        $this->assertArrayHasKey('translation', $history->json('data.entries.0.changes'));
        $this->assertSame('Updated post', Post::query()->findOrFail($id)->getTranslation('title', 'en'));
        $this->assertSame('Updated editor content', json_decode(app(\App\Services\Editor\EditorDocuments::class)->read(Post::query()->findOrFail($id), 'en'), true)['blocks'][0]['data']['text']);

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            '_version' => \App\Support\RecordVersion::token(Post::query()->findOrFail($id), 'posts:post'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'status' => 'pending',
            'slug' => 'post-history-test',
            'translation' => ['en' => ['title' => 'Blocked transition']],
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame('publish', Post::query()->findOrFail($id)->status);

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            '_version' => $created->json('data.version'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'status' => 'draft',
            'slug' => 'post-history-test',
            'translation' => ['en' => ['title' => 'Stale post']],
        ])->assertConflict();
        $this->assertSame('Updated post', Post::query()->findOrFail($id)->getTranslation('title', 'en'));
    }
}
