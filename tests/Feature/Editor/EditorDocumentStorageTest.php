<?php

namespace Tests\Feature\Editor;

use App\Models\EditorDocument;
use App\Services\Editor\EditorDocuments;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\Media\Models\Media;
use JobMetric\Media\Models\MediaRelation;
use JobMetric\Post\Models\Post;
use Tests\TestCase;

class EditorDocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_reopened_editor_resolves_nested_media_without_mutating_storage(): void
    {
        $service = app(EditorDocuments::class);
        $owner = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $blocks = [];
        foreach (['audio' => 'audio/wav', 'video' => 'video/webm'] as $type => $mime) {
            $media = Media::query()->create(['type' => 'f', 'name' => $type, 'mime_type' => $mime, 'extension' => explode('/', $mime)[1], 'disk' => 'public', 'size' => 10]);
            $blocks[] = ['type' => $type, 'data' => ['file' => ['id' => $media->id, 'url' => 'https://example.test/old'], 'caption' => '']];
        }
        $service->save($owner, 'fa', ['blocks' => [['type' => 'columns', 'data' => ['sections' => [['title' => 'Media', 'blocks' => $blocks]]]]]]);
        $stored = $service->read($owner, 'fa');
        $loaded = json_decode($service->readForEditor($owner, 'fa'), true);
        foreach ($loaded['blocks'][0]['data']['sections'][0]['blocks'] as $block) {
            $this->assertSame(route('admin.media-manager.content', $block['data']['file']['id']), $block['data']['file']['url']);
        }
        $this->assertSame($stored, $service->read($owner, 'fa'));
        $this->assertNull($service->readForEditor($owner, 'en'));
    }

    public function test_legacy_cleanup_dry_run_retains_content(): void
    {
        $owner = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $owner->translate('en', ['title' => 'Keep title', 'content' => '{"blocks":[]}']);
        $this->artisan('editor:clear-legacy')->assertSuccessful();
        $this->assertSame('{"blocks":[]}', $owner->fresh()->getTranslation('content', 'en'));
        $this->assertSame('Keep title', $owner->fresh()->getTranslation('title', 'en'));
    }

    public function test_languages_and_owners_have_independent_documents(): void
    {
        $service = app(EditorDocuments::class);
        $first = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $second = Post::query()->create(['type' => 'page', 'status' => 'draft']);
        $content = ['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'One']]]];
        $service->save($first, 'fa', $content);
        $service->save($second, 'fa', ['blocks' => []]);
        $this->assertNull($service->read($first, 'en'));
        $this->assertNull($first->getTranslation('content', 'fa'));
        $this->assertCount(2, EditorDocument::all());
        $this->assertSame('One', json_decode($service->read($first, 'fa'), true)['blocks'][0]['data']['text']);
    }

    public function test_media_belongs_to_document_and_removal_keeps_the_file(): void
    {
        $service = app(EditorDocuments::class);
        $owner = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $media = Media::query()->create(['type' => 'f', 'name' => 'Photo', 'mime_type' => 'image/png', 'extension' => 'png', 'disk' => 'public', 'size' => 10]);
        $document = $service->save($owner, 'en', ['blocks' => [['type' => 'image', 'data' => ['file' => ['id' => $media->id, 'url' => 'https://untrusted.example/image.png']]]]]);
        $this->assertNotSame('https://untrusted.example/image.png', $document->content['blocks'][0]['data']['file']['url']);
        $this->assertTrue(MediaRelation::query()->where('mediaable_type', $document->getMorphClass())->where('mediaable_id', $document->id)->where('media_id', $media->id)->exists());
        $owner->delete();
        $this->assertFalse(EditorDocument::query()->exists());
        $this->assertFalse(MediaRelation::query()->where('media_id', $media->id)->exists());
        $this->assertNotNull($media->fresh());
    }

    public function test_external_media_without_a_file_manager_id_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(EditorDocuments::class)->normalize(['blocks' => [['type' => 'image', 'data' => ['file' => ['url' => 'https://external.example/image.png']]]]]);
    }

    public function test_document_changes_roll_back_with_owner_transaction(): void
    {
        $owner = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        try {
            DB::transaction(function () use ($owner): void {
                app(EditorDocuments::class)->save($owner, 'en', ['blocks' => []]);
                throw new \RuntimeException('Abort owner save');
            });
        } catch (\RuntimeException) {
            $this->assertSame(0, EditorDocument::count());
        }
    }

    public function test_revision_restores_document_content(): void
    {
        $owner = Post::query()->create(['type' => 'post', 'status' => 'draft']);
        $service = app(EditorDocuments::class);
        $service->save($owner, 'en', ['blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Before']]]]);
        $revision = $owner->revisions()->create(['snapshot' => $owner->snapshot()]);
        $service->capture($revision, $service->snapshot($owner));
        $service->save($owner, 'en', ['blocks' => []]);
        $service->restore($owner, $revision);
        $this->assertSame('Before', json_decode($service->read($owner, 'en'), true)['blocks'][0]['data']['text']);
    }
}
