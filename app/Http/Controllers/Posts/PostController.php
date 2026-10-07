<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Facades\Hooks;
use App\Facades\Editor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\PostRequest;
use App\Rules\EditorContentRule;
use App\Support\EditorDriverRegistry;
use App\Support\LocaleCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\Domi\Facades\Domi;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Taxonomy\Models\Taxonomy;
use JobMetric\Media\Http\Resources\MediaResource;

final class PostController extends Controller
{
    public function index(string $type): View
    {
        $builder = PostTypeRegistry::for($type);
        Domi::setPlugins(['jquery', 'datatable']);
        return view('posts.index', ['type' => $type, 'typeLabel' => $builder->get()['label'] ?? $type]);
    }

    public function data(Request $request, string $type): JsonResponse
    {
        $search = trim((string) $request->input('search.value', ''));
        $locale = LocaleCatalog::resolve(app()->getLocale());
        $query = Post::query()->where('type', $type)->with('translations');
        $total = (clone $query)->count();
        if ($search !== '') {
            $query->whereHas('translations', fn ($translation) => $translation->where('locale', $locale)->where('field', 'title')->where('value', 'like', "%{$search}%"));
        }
        $filtered = (clone $query)->count();
        $query->orderByDesc('updated_at')->orderByDesc('id');
        $rows = $query->offset(max(0, (int) $request->input('start', 0)))->limit(min(100, max(1, (int) $request->input('length', 25))))->get();
        return response()->json(['ok' => true, 'data' => [
            'draw' => (int) $request->input('draw', 0), 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $rows->map(fn (Post $post) => ['id' => $post->id, 'title' => $post->getTranslation('title', $locale) ?? '', 'status' => $post->status, 'updated_at' => $post->updated_at?->toIso8601String(), 'edit_url' => route('admin.posts.edit', ['type' => $type, 'post' => $post->id]), 'delete_url' => route('admin.posts.destroy', ['type' => $type, 'post' => $post->id])]),
        ]]);
    }

    public function create(string $type): View { return $this->form($type, null); }
    public function edit(string $type, int $post): View { return $this->form($type, Post::query()->where('type', $type)->with(['translations', 'files'])->findOrFail($post)); }

    private function form(string $type, ?Post $post): View
    {
        $builder = PostTypeRegistry::for($type);
        $postType = new Post;
        $postType->type = $type;
        $taxonomyTypes = $postType->taxonomyAllowTypes();
        $editorConfig = Hooks::filter('jobic.editor.config', [
            'tools' => Hooks::filter('jobic.editor.tools', Editor::clientTools(), ['type' => $type, 'post_id' => $post?->id]),
            'minHeight' => 280,
        ], ['type' => $type, 'post_id' => $post?->id]);
        $metadata = $post?->getMetadata()?->all() ?? [];
        $taxonomyOptions = [];
        $taxonomyLabels = [];
        foreach ($taxonomyTypes as $collection => $definition) {
            $taxonomyLabels[$collection] = \JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($definition['type'])->getLabel();
            $taxonomyOptions[$collection] = Taxonomy::query()->where('type', $definition['type'])->where('status', true)->with('translations')->orderBy('id')->get()->map(fn (Taxonomy $taxonomy) => [
                'id' => $taxonomy->id, 'name' => $taxonomy->getTranslation('name') ?? '#'.$taxonomy->id,
            ])->all();
        }
        $selectedTaxonomies = [];
        if ($post) {
            foreach ($taxonomyTypes as $collection => $_) {
                $selectedTaxonomies[$collection] = $post->taxonomies()->wherePivot('collection', $collection)->pluck((new Taxonomy)->getTable().'.id')->map(fn ($id) => (int) $id)->all();
            }
        }
        $mediaCollections = ['base' => ['multiple' => false], ...($builder->get()['media-collections'] ?? [])];
        $selectedMedia = [];
        if ($post) {
            foreach ($mediaCollections as $collection => $_) {
                $selectedMedia[$collection] = $post->getMediaByCollection($collection)->get()->map(function ($media) {
                    $item = MediaResource::make($media)->resolve(request());
                    $item['url'] = $item['src'] ?? null;
                    $item['thumbnail_url'] = $item['src'] ?? null;
                    $item['group'] = $item['mime_group'] ?? 'document';
                    return $item;
                })->all();
            }
        }
        return view('posts.form', ['type' => $type, 'typeLabel' => $builder->get()['label'] ?? $type, 'record' => $post, 'revisions' => $post?->revisions()->get(['id', 'created_at']) ?? collect(), 'locales' => LocaleCatalog::all(), 'translationFields' => $builder->get()['translation-fields'] ?? ['title', 'excerpt', 'content'], 'editorConfig' => $editorConfig, 'customFields' => $builder->get()['custom-fields'] ?? [], 'metadata' => $metadata, 'taxonomyTypes' => $taxonomyTypes, 'taxonomyLabels' => $taxonomyLabels, 'taxonomyOptions' => $taxonomyOptions, 'selectedTaxonomies' => $selectedTaxonomies, 'mediaCollections' => $mediaCollections, 'selectedMedia' => $selectedMedia]);
    }

    public function store(PostRequest $request, string $type): JsonResponse { return $this->save($request, $type, null); }
    public function update(PostRequest $request, string $type, int $post): JsonResponse
    {
        return $this->save($request, $type, Post::query()->where('type', $type)->findOrFail($post));
    }

    private function save(PostRequest $request, string $type, ?Post $post): JsonResponse
    {
        $data = $request->validated();
        Hooks::action('jobic.posts.saving', [
            'type' => $type,
            'post_id' => $post?->id,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'fields' => array_keys($data),
        ]);
        $post = DB::transaction(function () use ($data, $type, $post): Post {
            $previousSnapshot = $post?->snapshot();
            $lastRevisionId = (int) ($post?->revisions()->max('id') ?? 0);
            $post ??= new Post;
            $post->type = $type;
            $post->fill(array_intersect_key($data, array_flip(['status', 'published_at', 'comment_status', 'password', 'slug'])));
            $translations = (array) ($data['translation'] ?? []);
            foreach ($translations as $locale => $fields) {
                foreach ($fields as $field => $value) {
                    if ($field !== 'content' || ($value !== '' && $value !== null && ! is_array($value) && ! is_string($value))) { continue; }
                    $content = is_string($value) ? json_decode($value, true) : $value;
                    if (is_array($content)) {
                        $content = Hooks::filter('jobic.editor.content', $content, ['type' => $type, 'locale' => $locale, 'post_id' => $post->id]);
                        $validator = Validator::make(['content' => $content], ['content' => [new EditorContentRule(app(EditorDriverRegistry::class))]]);
                        if ($validator->fails()) {
                            throw ValidationException::withMessages($validator->errors()->toArray());
                        }
                        $translations[$locale]['content'] = json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                }
            }
            if ($post->exists) {
                foreach ($translations as $locale => $fields) {
                    foreach ($fields as $field => $value) {
                        if ($post->getTranslation((string) $field, (string) $locale) === $value) {
                            unset($translations[$locale][$field]);
                        }
                    }
                    if ($translations[$locale] === []) {
                        unset($translations[$locale]);
                    }
                }
            }
            $post->translation = $translations;
            if (array_key_exists('metadata', $data)) {
                $post->metadata = (array) $data['metadata'];
            }
            $post->save();
            foreach ((array) ($data['taxonomies'] ?? []) as $collection => $ids) {
                foreach ($post->taxonomies()->wherePivot('collection', $collection)->get() as $old) { $post->taxonomies()->detach($old->id); }
                foreach ($ids as $id) { $post->attachTaxonomy((int) $id, (string) $collection); }
            }
            foreach ((array) ($data['media'] ?? []) as $collection => $ids) {
                foreach ($post->files()->wherePivot('collection', $collection)->get() as $old) { $post->files()->detach($old->id); }
                foreach ($ids as $id) { $post->attachMedia((int) $id, (string) $collection); }
            }
            $this->syncEditorMedia($post);

            if ($previousSnapshot !== null
                && $post->snapshot() !== $previousSnapshot
                && (int) ($post->revisions()->max('id') ?? 0) === $lastRevisionId) {
                $post->revisions()->create(['snapshot' => $previousSnapshot]);
            }

            return $post;
        });
        Hooks::notify('jobic.posts.saved', ['type' => $type, 'post_id' => $post->id, 'user_id' => $request->user()?->getAuthIdentifier(), 'fields' => array_keys($data)]);
        return response()->json(['ok' => true, 'message' => trans('posts.saved'), 'data' => ['id' => $post->id, 'edit_url' => route('admin.posts.edit', ['type' => $type, 'post' => $post->id])]], 200)->header('X-CSRF-TOKEN', csrf_token());
    }

    private function syncEditorMedia(Post $post): void
    {
        $mediaIds = [];
        foreach ($post->translations()->where('field', 'content')->pluck('value') as $content) {
            $document = is_string($content) ? json_decode($content, true) : $content;
            foreach ((array) ($document['blocks'] ?? []) as $block) {
                if (! is_array($block) || ($block['type'] ?? null) !== 'image') {
                    continue;
                }

                $id = $block['data']['file']['id'] ?? null;
                if (is_numeric($id) && (int) $id > 0) {
                    $mediaIds[] = (int) $id;
                }
            }
        }
        $mediaIds = array_values(array_unique($mediaIds));

        foreach ($post->getMediaByCollection('editor')->get() as $media) {
            if (! in_array((int) $media->id, $mediaIds, true)) {
                $post->files()->wherePivot('collection', 'editor')->detach($media->id);
            }
        }
        foreach ($mediaIds as $mediaId) {
            $post->attachMedia($mediaId, 'editor');
        }
    }

    public function destroy(Request $request, string $type, int $post): JsonResponse
    {
        $record = Post::query()->where('type', $type)->findOrFail($post);
        Hooks::action('jobic.posts.deleting', ['type' => $type, 'post_id' => $record->id, 'user_id' => $request->user()?->getAuthIdentifier()]);
        $record->delete();
        Hooks::notify('jobic.posts.deleted', ['type' => $type, 'post_id' => $post, 'user_id' => $request->user()?->getAuthIdentifier()]);
        return response()->json(['ok' => true, 'message' => trans('posts.deleted')]);
    }

    public function restoreRevision(Request $request, string $type, int $post, int $revision): JsonResponse
    {
        $record = Post::query()->where('type', $type)->findOrFail($post);
        $record->revisions()->findOrFail($revision);
        Hooks::action('jobic.posts.revision.restoring', ['type' => $type, 'post_id' => $record->id, 'revision_id' => $revision, 'user_id' => $request->user()?->getAuthIdentifier()]);
        DB::transaction(fn () => $record->restoreRevision($revision));
        Hooks::notify('jobic.posts.revision.restored', ['type' => $type, 'post_id' => $record->id, 'revision_id' => $revision, 'user_id' => $request->user()?->getAuthIdentifier()]);
        return response()->json(['ok' => true, 'message' => trans('posts.revision_restored')]);
    }
}
