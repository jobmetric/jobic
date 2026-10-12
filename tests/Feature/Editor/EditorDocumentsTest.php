<?php

namespace Tests\Feature\Editor;

use App\Facades\Editor;
use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;
use App\Rules\EditorContentRule;
use App\Support\EditorDriverRegistry;
use App\Support\EditorRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorDocumentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_registered_builtin_block_renders_representative_content(): void
    {
        $file = ['id' => 42, 'url' => 'https://example.test/media', 'name' => 'Example file'];
        foreach (app(EditorDriverRegistry::class)->all() as $key => $driver) {
            $data = ['text' => 'Example text', 'title' => 'Example title', 'caption' => 'Caption', 'message' => 'Message', 'code' => 'echo 1;', 'html' => '<b>Safe HTML</b>', 'formula' => 'x + y', 'height' => 24, 'level' => 2, 'name' => 'star', 'url' => 'https://example.test', 'style' => 'unordered', 'service' => 'youtube', 'source' => 'https://www.youtube.com/watch?v=abcdefghijk', 'embed' => 'https://www.youtube.com/embed/abcdefghijk', 'file' => $file, 'files' => [$file], 'content' => [['A', 'B']], 'items' => [['content' => 'Item', 'text' => 'Item', 'checked' => true, 'items' => []]], 'sections' => [['title' => 'Section', 'blocks' => [['type' => 'paragraph', 'data' => ['text' => 'Nested content']]]]]];
            $this->assertTrue($driver->validate($data), $key . ' fixture must validate');
            $html = app(EditorRenderer::class)->render(['blocks' => [['type' => $key, 'data' => $data]]], 0, true);
            $this->assertNotSame('', trim($html), $key . ' must produce output');
            $this->assertStringNotContainsString('@php', $html, $key . ' must compile Blade');
        }
    }

    public function test_audio_preview_uses_authorized_inline_content_for_nested_blocks(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $media = \JobMetric\Media\Models\Media::query()->create(['type' => 'f', 'name' => 'Audio', 'mime_type' => 'audio/mpeg', 'extension' => 'mp3', 'disk' => 'public', 'size' => 10]);
        $content = ['blocks' => [['type' => 'columns', 'data' => ['sections' => [['title' => 'Sound', 'blocks' => [['type' => 'audio', 'data' => ['file' => ['id' => $media->id, 'url' => 'https://untrusted.example/a.mp3'], 'caption' => 'Current caption']]]]]]]]];
        $response = $this->postJson(route('admin.posts.editor-preview', ['type' => 'post']), compact('content'))->assertOk();
        $this->assertStringContainsString('<audio controls', $response->json('html'));
        $this->assertStringContainsString(route('admin.media-manager.content', $media->id), $response->json('html'));
        $this->assertStringContainsString('Current caption', $response->json('html'));
        $this->assertStringNotContainsString('untrusted.example', $response->json('html'));
    }

    public function test_media_preview_accepts_empty_optional_captions(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        foreach (['audio' => 'audio/wav', 'video' => 'video/webm'] as $type => $mime) {
            $media = \JobMetric\Media\Models\Media::query()->create(['type' => 'f', 'name' => 'Sample', 'mime_type' => $mime, 'extension' => explode('/', $mime)[1], 'disk' => 'public', 'size' => 10]);
            $content = ['blocks' => [['type' => $type, 'data' => ['caption' => '', 'file' => ['id' => $media->id, 'url' => 'https://example.test/file']]]]];
            $content = json_encode($content, JSON_THROW_ON_ERROR);
            $response = $this->postJson(route('admin.posts.editor-preview', ['type' => 'post']), compact('content'))->assertOk();
            $this->assertStringContainsString('<' . $type . ' controls', $response->json('html'));
        }
    }

    public function test_preview_renders_a_draft_without_saving_a_post(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());
        $before = \JobMetric\Post\Models\Post::count();
        $response = $this->postJson(route('admin.posts.editor-preview', ['type' => 'post']), [
            'content' => ['blocks' => [['type' => 'paragraph', 'data' => ['text' => '<b>Preview</b><script>alert(1)</script>']]]],
        ])->assertOk();
        $this->assertStringContainsString('<b>Preview</b>', $response->json('html'));
        $this->assertStringNotContainsString('<script', $response->json('html'));
        $this->assertSame($before, \JobMetric\Post\Models\Post::count());
    }

    public function test_preview_requires_authentication_and_rejects_invalid_blocks(): void
    {
        $url = route('admin.posts.editor-preview', ['type' => 'page']);
        $this->postJson($url, [])->assertUnauthorized();
        $this->actingAs(\App\Models\User::factory()->create());
        $this->postJson($url, ['content' => ['blocks' => [['type' => 'unregistered', 'data' => []]]]])->assertUnprocessable();
    }

    public function test_discovered_drivers_validate_nested_documents_and_reject_invalid_children(): void
    {
        $registry = app(EditorDriverRegistry::class);
        $this->assertTrue($registry->has('columns'));
        $rule = new EditorContentRule($registry);
        $document = ['blocks' => [['type' => 'columns', 'data' => ['sections' => [
            ['title' => 'Column', 'blocks' => [['type' => 'header', 'data' => ['text' => 'Title', 'level' => 2]]]],
        ]]]]];
        $errors = [];
        $rule->validate('content', $document, function ($message) use (&$errors) { $errors[] = $message; });
        $this->assertSame([], $errors);
        $document['blocks'][0]['data']['sections'][0]['blocks'][0]['data']['level'] = 99;
        $rule->validate('content', $document, function ($message) use (&$errors) { $errors[] = $message; });
        $this->assertNotEmpty($errors);
    }

    public function test_unavailable_blocks_must_match_the_original_saved_data(): void
    {
        $original = ['blocks' => [['type' => 'retiredTool', 'data' => ['text' => 'Original']]]];
        $rule = new EditorContentRule(app(EditorDriverRegistry::class), $original);
        $errors = [];
        $rule->validate('content', $original, function ($message) use (&$errors) { $errors[] = $message; });
        $this->assertSame([], $errors);
        $changed = $original;
        $changed['blocks'][0]['data']['text'] = 'Changed';
        $rule->validate('content', $changed, function ($message) use (&$errors) { $errors[] = $message; });
        $this->assertNotEmpty($errors);
    }

    public function test_output_uses_driver_views_and_removes_executable_markup(): void
    {
        $html = Editor::render(['blocks' => [['type' => 'paragraph', 'data' => ['text' => '<strong>Safe</strong><script>alert(1)</script><a href="javascript:alert(1)">Link</a>']]]]);
        $this->assertStringContainsString('<strong>Safe</strong>', $html);
        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('alert(1)', $html);
        $this->assertSame('', app(EditorRenderer::class)->url('javascript:alert(1)'));
    }

    public function test_templates_are_available_and_duplicate_registration_is_rejected(): void
    {
        $this->assertCount(10, Editor::templates()->forType('post'));
        foreach (['fa', 'en'] as $locale) {
            app()->setLocale($locale);
            $templates = Editor::templates()->forType('post');
            foreach ($templates as $template) {
                $this->assertNotEmpty($template['preview']);
                $this->assertGreaterThanOrEqual(14, count($template['document']['blocks']));
            }
            $this->assertSame(__('editor-patterns.intro'), $templates[0]['document']['blocks'][1]['data']['text']);
            $this->assertNotEmpty($templates[1]['document']['blocks'][0]['data']['text']);
            $this->assertContains('gallery', array_column($templates[2]['document']['blocks'], 'type'));
        }
        $this->expectException(\InvalidArgumentException::class);
        Editor::register('header')->register();
    }

    public function test_asset_route_never_serves_arbitrary_driver_files(): void
    {
        $this->get('/editor-assets/header/module')->assertNotFound();
        $this->get('/editor-assets/header/driver.json')->assertNotFound();
    }

    public function test_external_driver_is_discovered_once_and_only_declared_assets_are_served(): void
    {
        $path = base_path('tests/Fixtures/Editor/Valid');
        Editor::addPath($path)->addPath($path);
        $this->assertTrue(Editor::has('externalNotice'));
        $this->get('/editor-assets/externalNotice/module')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/editor-assets/externalNotice/style')->assertOk();
        $this->get('/editor-assets/externalNotice/view')->assertNotFound();
        $this->assertStringContainsString('&lt;script&gt;', Editor::render(['blocks' => [
            ['type' => 'externalNotice', 'data' => ['text' => '<script>']],
        ]]));
    }

    public function test_discovery_rejects_assets_outside_the_driver_folder_without_publishing_them(): void
    {
        $registry = app(EditorDriverRegistry::class);
        try {
            $registry->addPath(base_path('tests/Fixtures/Editor/Invalid'));
            $this->fail('An escaped asset path must be rejected.');
        } catch (\InvalidArgumentException) {
            $this->assertFalse($registry->has('unsafeNotice'));
            $this->assertNull($registry->asset('unsafeNotice', 'module'));
        }
    }
}

class ExternalNoticeDriver implements EditorBlockDriver
{
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('Notice')->schema(['text' => 'string']);
    }
}
