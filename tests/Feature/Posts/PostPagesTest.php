<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Facades\AdminMenu;
use App\Models\User;
use App\Services\Posts\PostFlowProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Flow\Models\Flow;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Facades\PostTypeRegistry;
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

        $this->getJson(route('admin.posts.data', ['type' => 'post']).'?categories='.$termId)
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1)->assertJsonPath('data.data.0.id', $matching->id);
    }

    public function test_external_post_type_can_register_a_custom_list_filter(): void
    {
        $this->actingAs(User::factory()->create());
        PostTypeRegistry::register('note')->translationFields(['title'])->option('filters', ['visibility' => [
            'label' => 'Visibility', 'options' => ['draft' => 'Draft'],
            'apply' => static fn ($query, $value) => $query->where('status', $value),
        ]]);
        Post::query()->create(['type' => 'note', 'status' => 'draft']);
        Post::query()->create(['type' => 'note', 'status' => 'publish']);

        $this->getJson(route('admin.posts.data', ['type' => 'note']).'?visibility=draft')
            ->assertOk()->assertJsonPath('data.recordsFiltered', 1);
    }

    public function test_post_form_only_contains_the_current_locale_translation_fields(): void
    {
        $this->withoutVite();
        app()->setLocale('en');
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.posts.create', ['type' => 'post']))
            ->assertOk()->assertSee('translation[en][title]', false)
            ->assertDontSee('translation[fa][title]', false);
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

        $this->get(route('admin.posts.create', ['type' => 'post']))
            ->assertOk()
            ->assertSee('crud-taxonomy-layout has-sidebar', false)
            ->assertSee('crud-translation-group post-translation-group', false)
            ->assertSee('post-active-locale', false)
            ->assertDontSee('data-locale-tab', false)
            ->assertSee('post-media-field', false)
            ->assertSee('post-url-field', false)
            ->assertSee('data-taxonomy-quick-add', false)
            ->assertSee('data-post-datepicker', false);
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
            'translation' => ['en' => ['title' => 'Original post', 'content' => json_encode(['blocks' => []])]],
        ])->assertOk();
        $id = (int) $created->json('data.id');
        $this->assertNotNull(Post::query()->findOrFail($id)->boundFlow());
        $this->assertDatabaseHas(config('workflow.tables.flow_instance', 'flow_instances'), [
            'instanceable_type' => Post::class,
            'instanceable_id' => $id,
        ]);

        $this->getJson(route('admin.posts.data', ['type' => 'post']))
            ->assertOk()
            ->assertJsonPath('data.data.0.title', 'Original post');

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            '_version' => $created->json('data.version'),
            'status' => 'publish',
            'slug' => 'post-history-test',
            'translation' => ['en' => ['title' => 'Updated post', 'content' => json_encode(['blocks' => []])]],
        ])->assertOk();

        $history = $this->getJson(route('admin.posts.history', ['type' => 'post', 'post' => $id]))->assertOk();
        $this->assertSame('update', $history->json('data.entries.0.operation'));
        $this->assertArrayHasKey('translation', $history->json('data.entries.0.changes'));
        $this->assertSame('Updated post', Post::query()->findOrFail($id)->getTranslation('title', 'en'));

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            '_version' => \App\Support\RecordVersion::token(Post::query()->findOrFail($id), 'posts:post'),
            'status' => 'pending',
            'slug' => 'post-history-test',
            'translation' => ['en' => ['title' => 'Blocked transition']],
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertSame('publish', Post::query()->findOrFail($id)->status);

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            '_version' => $created->json('data.version'),
            'status' => 'draft',
            'slug' => 'post-history-test',
            'translation' => ['en' => ['title' => 'Stale post']],
        ])->assertConflict();
        $this->assertSame('Updated post', Post::query()->findOrFail($id)->getTranslation('title', 'en'));
    }
}
