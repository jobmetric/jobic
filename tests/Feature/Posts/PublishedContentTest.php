<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Facades\PostTypeRegistry;
use Tests\TestCase;

/** Ensure public delivery never exposes drafts or another language. */
class PublishedContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_jobic_form_projects_editor_document_and_translation_into_live_api(): void
    {
        $this->actingAs(User::factory()->create());
        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=en', [
            'status' => 'publish', 'slug' => 'public-story',
            'translation' => ['en' => ['title' => 'Public story', 'content' => json_encode([
                'blocks' => [['id' => 'intro', 'type' => 'paragraph', 'data' => ['text' => 'Public body']]],
            ])]],
        ])->assertOk();
        $post = Post::findOrFail((int) $created->json('data.id'));

        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $post->id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'Public story')
            ->assertJsonPath('data.content.content.blocks.0.data.text', 'Public body');
        $this->assertNull($post->getTranslation('content', 'en'));
    }

    public function test_public_api_returns_only_the_live_revision_for_the_requested_language(): void
    {
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $lifecycle = app(PostLifecycle::class);
        $stream = $lifecycle->saveDraft($post, 'fa', ['title' => 'Published']);
        $lifecycle->approve($post, 'fa', (int) $stream->draft_revision_id);
        $lifecycle->publish($post, 'fa', (int) $stream->draft_revision_id);
        $lifecycle->saveDraft($post, 'fa', ['title' => 'Private draft']);
        $lifecycle->saveDraft($post, 'en', ['title' => 'English draft']);

        $url = route('api.content.show', ['type' => 'post', 'post' => $post->id]);
        $this->getJson($url.'?locale=fa')->assertOk()
            ->assertJsonPath('data.content.title', 'Published')
            ->assertDontSee('Private draft');
        $this->getJson($url.'?locale=en')->assertNotFound();
        $this->getJson(route('api.content.show', ['type' => 'page', 'post' => $post->id]).'?locale=fa')->assertNotFound();

        $post->password = 'secret';
        $post->save();
        $this->getJson($url.'?locale=fa')->assertNotFound();
    }

    public function test_public_projection_respects_registered_field_ownership(): void
    {
        PostTypeRegistry::register('memo')->translationFields(['title'])
            ->metadataFields(['public_note', 'internal_note'])
            ->fieldOwnership(['comment_status' => 'internal', 'metadata.internal_note' => 'internal']);
        $post = Post::query()->create(['type' => 'memo', 'status' => 'draft', 'comment_status' => true]);
        $post->storeMetadataBatch(['public_note' => 'Public', 'internal_note' => 'Secret']);

        $payload = app(\App\Services\Posts\PostPublicationProjection::class)
            ->sharedFields($post, $post->snapshot());

        $this->assertSame('Public', $payload['metadata']['public_note']);
        $this->assertArrayNotHasKey('internal_note', $payload['metadata']);
        $this->assertArrayNotHasKey('comment_status', $payload);
    }

    public function test_restoring_a_saved_post_revision_creates_a_new_live_release(): void
    {
        $this->actingAs(User::factory()->create());
        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=en', [
            'status' => 'publish', 'slug' => 'revision-story',
            'translation' => ['en' => ['title' => 'Original title']],
        ])->assertOk();
        $id = (int) $created->json('data.id');
        $updated = $this->putJson(route('admin.posts.update', ['type' => 'post', 'post' => $id]).'?locale=en', [
            'status' => 'publish', 'slug' => 'revision-story', '_version' => $created->json('data.version'),
            '_edit_lock' => $created->json('data.edit_lock'),
            'translation' => ['en' => ['title' => 'Current live title']],
        ])->assertOk();
        $post = Post::findOrFail($id);
        $oldLive = \JobMetric\Post\Models\PostPublicationStream::query()
            ->where('post_id', $id)->where('locale', '*')->value('live_revision_id');
        $revision = $post->revisions()->oldest('id')->firstOrFail();
        $this->postJson(route('admin.posts.revisions.restore', [
            'type' => 'post', 'post' => $id, 'revision' => $revision->id,
        ]), ['_edit_lock' => $updated->json('data.edit_lock')])->assertOk();

        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $id]).'?locale=en')
            ->assertOk()->assertJsonPath('data.content.title', 'Original title');
        $this->assertNotSame($oldLive, \JobMetric\Post\Models\PostPublicationStream::query()
            ->where('post_id', $id)->where('locale', '*')->value('live_revision_id'));
        $this->assertSame('Original title', $post->fresh()->getTranslation('title', 'en'));
    }

    public function test_disabling_a_simple_post_withdraws_its_live_api_revision(): void
    {
        $this->actingAs(User::factory()->create());
        $created = $this->postJson(route('admin.posts.store', ['type' => 'post']).'?locale=en', [
            'status' => 'publish', 'slug' => 'withdrawn-story',
            'translation' => ['en' => ['title' => 'Initially public']],
        ])->assertOk();
        $id = (int) $created->json('data.id');
        $publicUrl = route('api.content.show', ['type' => 'post', 'post' => $id]).'?locale=en';
        $this->getJson($publicUrl)->assertOk();

        $this->postJson(route('admin.posts.bulk', ['type' => 'post']), [
            'operation' => 'deactivate', 'ids' => [$id],
        ])->assertOk();

        $this->getJson($publicUrl)->assertNotFound();
        $this->assertSame('disabled', Post::findOrFail($id)->status);
    }
}
