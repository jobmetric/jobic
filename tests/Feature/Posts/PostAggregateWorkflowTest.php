<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Models\User;
use App\Support\RecordVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostLocaleLock;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Models\PostScheduledAction;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Services\PostStreamWorkflow;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use Tests\TestCase;

/** Verify that one post workflow publishes a complete immutable multilingual release. */
class PostAggregateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_and_transitioning_publishes_the_whole_post_without_per_language_approval(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $definitions = app(PostWorkflowDefinitions::class);
        $draft = $definitions->saveDraft('post', [
            'states' => [
                ['id' => 'writing', 'status' => 'writing', 'content_status' => 'editable', 'name' => 'Writing', 'is_start' => true, 'is_terminal' => false],
                ['id' => 'live', 'status' => 'live', 'content_status' => 'publish', 'name' => 'Live', 'is_start' => false, 'is_terminal' => false],
            ],
            'transitions' => [
                ['id' => 'release', 'from' => 'writing', 'to' => 'live', 'slug' => 'release', 'label' => 'Release', 'tasks' => []],
                ['id' => 'revise', 'from' => 'live', 'to' => 'writing', 'slug' => 'revise', 'label' => 'Revise', 'tasks' => []],
            ],
        ], true, 0);
        $preview = $definitions->preview('post', (int) $draft->id);
        $definitions->activate('post', (int) $draft->id, $preview['fingerprint'], []);

        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=en', [
            'status' => 'editable', 'slug' => 'one-release',
            'translation' => ['en' => ['title' => 'English title']],
        ])->assertOk();
        $this->assertStringContainsString('data-post-lifecycle', $created->json('data.workflow_card_html'));
        $this->assertStringContainsString('data-post-workflow-details-open', $created->json('data.workflow_card_html'));
        $this->assertStringContainsString('data-post-workflow-details-dialog', $created->json('data.workflow_card_html'));
        $this->assertStringContainsString('post-lifecycle__versions', $created->json('data.workflow_card_html'));
        $this->assertStringContainsString('data-post-workflow-preview-dialog', $created->json('data.workflow_preview_html'));
        $this->assertTrue($created->json('data.editable'));
        $id = (int) $created->json('data.id');
        $this->assertSame('editable', Post::findOrFail($id)->status);
        $this->assertSame(1, PostPublicationStream::where('post_id', $id)->count());
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $id]).'?locale=en')
            ->assertOk()->assertSee('data-post-lifecycle', false)
            ->assertSee('data-post-workflow-preview-dialog', false)
            ->assertDontSee('data-post-lifecycle-dialog', false);

        $actor->forceFill(['ui_preferences' => ['locale' => 'fa']])->save();
        $translated = $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=fa', [
            'status' => 'editable', 'slug' => 'one-release',
            '_version' => $created->json('data.version'), '_edit_lock' => $created->json('data.edit_lock'),
            'translation' => ['fa' => ['title' => 'عنوان فارسی']],
        ])->assertOk();
        $actor->forceFill(['ui_preferences' => ['locale' => 'en']])->save();

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            'status' => 'editable', 'slug' => 'one-release',
            '_version' => $translated->json('data.version'), '_edit_lock' => $translated->json('data.edit_lock'),
            'translation' => ['en' => ['title' => 'This must roll back']],
            'workflow_transition' => 'missing',
        ])->assertUnprocessable();
        $this->assertSame('English title', Post::findOrFail($id)->getTranslation('title', 'en'));

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            'status' => 'editable', 'slug' => 'one-release',
            '_version' => $translated->json('data.version'), '_edit_lock' => $translated->json('data.edit_lock'),
            'translation' => ['en' => ['title' => 'English release']],
            'workflow_transition' => 'release',
        ])->assertOk();
        $this->assertSame('publish', Post::findOrFail($id)->status);
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $id]).'?locale=en')
            ->assertOk()->assertSee('data-form-read-only="1"', false)
            ->assertSee('class="post-media-controls" disabled', false)
            ->assertSee('class="post-taxonomy-options"', false)
            ->assertDontSee('data-revision-restore', false);
        $this->putJson(route('admin.posts.translation.update', ['type' => 'post', 'post' => $id, 'locale' => 'en']), [
            '_version' => RecordVersion::token(Post::findOrFail($id), 'posts:post'),
            'fields' => ['title' => 'Must not bypass published status'],
        ])->assertUnprocessable();
        $this->assertSame('English release', Post::findOrFail($id)->getTranslation('title', 'en'));
        $publishedOwner = Post::findOrFail($id);
        $ownerRevision = $publishedOwner->revisions()->create(['snapshot' => $publishedOwner->snapshot()]);
        $this->postJson(route('admin.posts.revisions.restore', [
            'type' => 'post', 'post' => $id, 'revision' => $ownerRevision->id,
        ]), ['_edit_lock' => $translated->json('data.edit_lock')])->assertStatus(409);
        $this->get(route('admin.posts.workflow.index', ['type' => 'post', 'post' => $id]))
            ->assertOk()->assertSee('"currentStatus":"live"', false);
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'English release');
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $id]).'?locale=fa')
            ->assertOk()->assertJsonPath('data.content.title', 'عنوان فارسی');
        $this->assertTrue(Post::query()->liveInLocale('en')->whereKey($id)->exists());
        $this->assertFalse(Post::query()->liveInLocale('de')->whereKey($id)->exists());

        $stream = PostPublicationStream::where('post_id', $id)->where('locale', '*')->firstOrFail();
        $this->postJson(route('admin.posts.lifecycle.command', ['type' => 'post', 'post' => $id]), [
            'operation' => 'transition', 'locale' => '*', 'version' => $stream->version,
            'revision_id' => $stream->draft_revision_id, 'transition' => 'revise',
            '_edit_lock' => $translated->json('data.edit_lock'),
        ])->assertOk();
        $this->assertSame('editable', Post::findOrFail($id)->status);
        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            'status' => 'editable', 'slug' => 'one-release',
            '_version' => RecordVersion::token(Post::findOrFail($id), 'posts:post'),
            '_edit_lock' => $translated->json('data.edit_lock'),
            'translation' => ['en' => ['title' => 'Unpublished revision']],
        ])->assertOk();
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'English release');
    }

    public function test_a_transition_can_lock_one_language_without_locking_the_other(): void
    {
        $actor = User::factory()->create();
        $actor->forceFill(['ui_preferences' => ['locale' => 'fa']])->save();
        $this->actingAs($actor);
        $definitions = app(PostWorkflowDefinitions::class);
        $draft = $definitions->saveDraft('post', [
            'states' => [
                ['id' => 'writing', 'status' => 'writing', 'content_status' => 'editable', 'name' => 'Writing', 'is_start' => true, 'is_terminal' => false],
                ['id' => 'review', 'status' => 'review', 'content_status' => 'editable', 'name' => 'Review', 'is_start' => false, 'is_terminal' => false],
            ],
            'transitions' => [[
                'id' => 'review', 'from' => 'writing', 'to' => 'review', 'slug' => 'review', 'label' => 'Review',
                'tasks' => [[
                    'driver' => \JobMetric\Post\Tasks\LockPostLanguage::class,
                    'config' => ['locale' => 'fa'], 'status' => true,
                ]],
            ]],
        ], true, 0);
        $preview = $definitions->preview('post', (int) $draft->id);
        $definitions->activate('post', (int) $draft->id, $preview['fingerprint'], []);

        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=fa', [
            'status' => 'editable', 'slug' => 'language-lock',
            'translation' => ['fa' => ['title' => 'متن فارسی']],
        ])->assertOk();
        $id = (int) $created->json('data.id');
        $stream = PostPublicationStream::where('post_id', $id)->where('locale', '*')->firstOrFail();
        app(PostStreamWorkflow::class)->transition(Post::findOrFail($id), '*', 'review',
            (int) $stream->draft_revision_id, (int) $stream->version, $actor);
        $this->assertTrue(PostLocaleLock::where('post_id', $id)->where('locale', 'fa')->firstOrFail()->locked);
        $owner = Post::findOrFail($id);
        $ownerRevision = $owner->revisions()->create(['snapshot' => $owner->snapshot()]);
        $this->postJson(route('admin.posts.revisions.restore', [
            'type' => 'post', 'post' => $id, 'revision' => $ownerRevision->id,
        ]), ['_edit_lock' => $created->json('data.edit_lock')])->assertStatus(409);
        $this->get(route('admin.posts.edit', ['type' => 'post', 'post' => $id]).'?locale=fa')
            ->assertOk()->assertSee('data-form-read-only="0"', false)
            ->assertSee('id="post-locale-fa"', false)
            ->assertSee('این زبان با گردش‌کار قفل شده است');

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=fa', [
            'status' => 'editable', 'slug' => 'language-lock',
            '_version' => RecordVersion::token(Post::findOrFail($id), 'posts:post'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'translation' => ['fa' => ['title' => 'تغییر ممنوع']],
        ])->assertStatus(422);
        $this->assertSame('متن فارسی', Post::findOrFail($id)->getTranslation('title', 'fa'));

        $this->putJson(route('admin.posts.translation.update', ['type' => 'post', 'post' => $id, 'locale' => 'fa']), [
            '_version' => RecordVersion::token(Post::findOrFail($id), 'posts:post'),
            'fields' => ['title' => 'تغییر ممنوع'],
        ])->assertUnprocessable();
        $this->assertSame('متن فارسی', Post::findOrFail($id)->getTranslation('title', 'fa'));

        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=fa', [
            'status' => 'editable', 'slug' => 'language-lock-shared',
            '_version' => RecordVersion::token(Post::findOrFail($id), 'posts:post'),
            '_edit_lock' => $created->json('data.edit_lock'),
        ])->assertOk();
        $this->assertSame('متن فارسی', Post::findOrFail($id)->getTranslation('title', 'fa'));

        $actor->forceFill(['ui_preferences' => ['locale' => 'en']])->save();
        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            'status' => 'editable', 'slug' => 'language-lock-shared',
            '_version' => RecordVersion::token(Post::findOrFail($id), 'posts:post'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'translation' => ['en' => ['title' => 'English remains editable']],
        ])->assertOk();
    }

    public function test_future_release_keeps_the_previous_live_revision_until_the_due_action_runs_once(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $definitions = app(PostWorkflowDefinitions::class);
        $draft = $definitions->saveDraft('post', [
            'states' => [
                ['id' => 'writing', 'status' => 'writing', 'content_status' => 'editable', 'name' => 'Writing', 'is_start' => true, 'is_terminal' => false],
                ['id' => 'live', 'status' => 'live', 'content_status' => 'publish', 'name' => 'Live', 'is_start' => false, 'is_terminal' => false],
                ['id' => 'planned', 'status' => 'planned', 'content_status' => 'future', 'name' => 'Planned', 'is_start' => false, 'is_terminal' => false],
            ],
            'transitions' => [
                ['id' => 'release', 'from' => 'writing', 'to' => 'live', 'slug' => 'release', 'label' => 'Release', 'tasks' => []],
                ['id' => 'revise', 'from' => 'live', 'to' => 'writing', 'slug' => 'revise', 'label' => 'Revise', 'tasks' => []],
                ['id' => 'plan', 'from' => 'writing', 'to' => 'planned', 'slug' => 'plan', 'label' => 'Plan', 'tasks' => []],
            ],
        ], true, 0);
        $preview = $definitions->preview('post', (int) $draft->id);
        $definitions->activate('post', (int) $draft->id, $preview['fingerprint'], []);

        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=en', [
            'status' => 'editable', 'slug' => 'scheduled-release',
            'translation' => ['en' => ['title' => 'Original']],
        ])->assertOk();
        $post = Post::findOrFail((int) $created->json('data.id'));
        $stream = PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->firstOrFail();
        $workflow = app(PostStreamWorkflow::class);
        $workflow->transition($post, '*', 'release', (int) $stream->draft_revision_id, (int) $stream->version, $actor);
        $stream->refresh();
        $workflow->transition($post->fresh(), '*', 'revise', (int) $stream->draft_revision_id, (int) $stream->version, $actor);
        $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $post->id]).'?locale=en', [
            'status' => 'editable', 'slug' => 'scheduled-release',
            '_version' => RecordVersion::token($post->fresh(), 'posts:post'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'translation' => ['en' => ['title' => 'Scheduled update']],
        ])->assertOk();
        $stream->refresh();
        $workflow->transition($post->fresh(), '*', 'plan', (int) $stream->draft_revision_id,
            (int) $stream->version, $actor, now()->addHour());
        $post->refresh();
        $this->assertSame('future', $post->status);
        $this->assertTrue(Post::query()->published()->whereKey($post->id)->exists());
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $post->id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'Original');

        $schedule = PostScheduledAction::where('stream_id', $stream->id)->firstOrFail();
        $this->travel(2)->hours();
        $this->assertTrue(app(PostLifecycle::class)->execute((int) $schedule->id));
        $this->assertFalse(app(PostLifecycle::class)->execute((int) $schedule->id));
        $this->assertSame('publish', $post->fresh()->status);
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $post->id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'Scheduled update');
    }

    public function test_archive_keeps_direct_access_but_disabled_hides_the_live_release(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);
        $definitions = app(PostWorkflowDefinitions::class);
        $draft = $definitions->saveDraft('post', [
            'states' => [
                ['id' => 'writing', 'status' => 'writing', 'content_status' => 'editable', 'name' => 'Writing', 'is_start' => true, 'is_terminal' => false],
                ['id' => 'live', 'status' => 'live', 'content_status' => 'publish', 'name' => 'Live', 'is_start' => false, 'is_terminal' => false],
                ['id' => 'archived', 'status' => 'archived', 'content_status' => 'archive', 'name' => 'Archived', 'is_start' => false, 'is_terminal' => false],
                ['id' => 'hidden', 'status' => 'hidden', 'content_status' => 'disabled', 'name' => 'Hidden', 'is_start' => false, 'is_terminal' => false],
            ],
            'transitions' => [
                ['id' => 'release', 'from' => 'writing', 'to' => 'live', 'slug' => 'release', 'label' => 'Release', 'tasks' => []],
                ['id' => 'archive', 'from' => 'live', 'to' => 'archived', 'slug' => 'archive', 'label' => 'Archive', 'tasks' => []],
                ['id' => 'hide', 'from' => 'archived', 'to' => 'hidden', 'slug' => 'hide', 'label' => 'Hide', 'tasks' => []],
            ],
        ], true, 0);
        $preview = $definitions->preview('post', (int) $draft->id);
        $definitions->activate('post', (int) $draft->id, $preview['fingerprint'], []);
        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=en', [
            'status' => 'editable', 'slug' => 'archive-release',
            'translation' => ['en' => ['title' => 'Archive me']],
        ])->assertOk();
        $post = Post::findOrFail((int) $created->json('data.id'));
        $stream = PostPublicationStream::where('post_id', $post->id)->where('locale', '*')->firstOrFail();
        foreach (['release', 'archive'] as $transition) {
            app(PostStreamWorkflow::class)->transition($post->fresh(), '*', $transition,
                (int) $stream->refresh()->draft_revision_id, (int) $stream->version, $actor);
        }
        $this->assertSame('archive', $post->fresh()->status);
        $this->assertFalse(Post::query()->published()->whereKey($post->id)->exists());
        $this->assertTrue(Post::query()->liveInLocale('en')->whereKey($post->id)->exists());
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $post->id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'Archive me');

        app(PostStreamWorkflow::class)->transition($post->fresh(), '*', 'hide',
            (int) $stream->refresh()->draft_revision_id, (int) $stream->version, $actor);
        $this->assertFalse(Post::query()->liveInLocale('en')->whereKey($post->id)->exists());
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $post->id]).'?locale=en')
            ->assertNotFound();
    }
}
