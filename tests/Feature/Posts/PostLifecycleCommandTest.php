<?php

declare(strict_types=1);

namespace Tests\Feature\Posts;

use App\Models\User;
use App\Services\Taxonomies\TaxonomyEditLocks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Services\PostLifecycle;
use Tests\TestCase;

/** Require the owner edit lease and exact stream version for public actions. */
class PostLifecycleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_command_rejects_missing_lease_and_stale_version(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $post = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $lifecycle = app(PostLifecycle::class);
        $stream = $lifecycle->saveDraft($post, '*', [
            'languages' => ['en' => ['title' => 'Exact version', 'content' => ['blocks' => []]]],
            'shared' => [],
        ]);
        $url = route('admin.posts.lifecycle.command', ['type' => 'post', 'post' => $post->id]);
        $payload = ['operation' => 'approve', 'locale' => '*',
            'revision_id' => $stream->draft_revision_id, 'version' => $stream->version];

        $this->postJson($url, [...$payload, 'locale' => 'en'])->assertUnprocessable();

        $this->postJson($url, $payload)->assertStatus(423);
        $lease = app(TaxonomyEditLocks::class)->acquire('posts:post', (int) $post->id, $user)['lock'];
        $payload['_edit_lock'] = $lease['token'];
        $this->postJson($url, $payload)->assertOk();
        $this->postJson($url, $payload)->assertStatus(409);

        $payload['operation'] = 'publish';
        $payload['version'] = $stream->refresh()->version;
        $this->postJson($url, $payload)->assertOk();
        $this->getJson(route('api.content.show', ['type' => 'post', 'post' => $post->id]).'?locale=en')
            ->assertJsonPath('data.content.title', 'Exact version');
    }
}
