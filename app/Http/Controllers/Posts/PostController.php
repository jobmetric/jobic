<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Facades\Hooks;
use App\Facades\Editor;
use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\BulkPostRequest;
use App\Http\Requests\Posts\PostRequest;
use App\Http\Requests\Posts\PostTranslationRequest;
use App\Rules\EditorContentRule;
use App\Support\EditorDriverRegistry;
use App\Support\LocaleCatalog;
use App\Support\LocalizedDate;
use App\Support\Page;
use App\Support\RecordVersion;
use App\Services\History\RecordHistory;
use App\Services\Posts\PostWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Taxonomy\Models\Taxonomy;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Media\Http\Resources\MediaResource;
use JobMetric\Translation\Models\Translation;

final class PostController extends Controller
{
    public function index(string $type): View
    {
        $builder = PostTypeRegistry::for($type);
        $this->preparePage('list');
        $postType = new Post;
        $postType->type = $type;
        $statusLabels = app(PostWorkflow::class)->allStatusOptions($postType, LocaleCatalog::resolve(app()->getLocale())) ?: trans('posts.statuses');
        $languages = collect(LocaleCatalog::all())->map(fn (array $language, string $locale): array => [
            'name' => $language['name'] ?? strtoupper($locale),
            'flag_url' => LocaleCatalog::flagUrl($language['flag'] ?? null),
            'direction' => $language['direction'] ?? 'auto',
        ])->all();
        Page::addLocalize('postPage', [
            'type' => $type,
            'dataUrl' => route('admin.posts.data', ['type' => $type]),
            'createUrl' => route('admin.posts.create', ['type' => $type]),
            'bulkUrl' => route('admin.posts.bulk', ['type' => $type]),
            'activeLocale' => LocaleCatalog::resolve(app()->getLocale()),
            'languages' => $languages,
            'hasMedia' => $this->mediaCollections($builder->get()) !== [],
            'statuses' => $statusLabels,
            'translationFields' => $builder->get()['translation-fields'] ?? ['title', 'excerpt', 'content'],
            'translationUrl' => route('admin.posts.translation', ['type' => $type, 'post' => '__ID__', 'locale' => '__LOCALE__']),
            'translationSaveUrl' => route('admin.posts.translation.update', ['type' => $type, 'post' => '__ID__', 'locale' => '__LOCALE__']),
            'editor' => [
                'tools' => Hooks::filter('jobic.editor.tools', Editor::clientTools(), ['type' => $type, 'post_id' => null]),
                'uploadUrl' => route('admin.media-manager.upload'),
                'messages' => trans('posts.editor'),
            ],
        ]);
        return view('posts.index', [
            'type' => $type,
            'typeLabel' => $builder->get()['label'] ?? $type,
            'description' => $builder->get()['description'] ?? '',
            'languages' => $languages,
            'activeLocale' => LocaleCatalog::resolve(app()->getLocale()),
            'hasMedia' => $this->mediaCollections($builder->get()) !== [],
            'statusLabels' => $statusLabels,
        ]);
    }

    public function data(Request $request, string $type): JsonResponse
    {
        $search = trim((string) $request->input('search.value', ''));
        $status = trim((string) $request->input('status', ''));
        $locale = LocaleCatalog::resolve(app()->getLocale());
        $query = Post::query()->where('type', $type)->with(['translations', 'files']);
        $total = (clone $query)->count();
        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->whereHas('translations', fn ($translation) => $translation->where('locale', $locale)->where('field', 'title')->where('value', 'like', "%{$search}%"));
        }
        $filtered = (clone $query)->count();
        $sortColumn = (int) $request->input('order.0.column', 1);
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        if ($sortColumn === 1) {
            $query->orderBy(Translation::query()->select('value')
                ->whereColumn('translatable_id', (new Post)->qualifyColumn('id'))
                ->where('translatable_type', (new Post)->getMorphClass())
                ->where('locale', $locale)->where('field', 'title')->orderByDesc('version')->limit(1), $direction);
        } elseif ($sortColumn === 2) {
            $query->orderBy('status', $direction);
        } elseif ($sortColumn === 3) {
            $query->orderBy('updated_at', $direction)->orderBy('id', $direction);
        } else {
            $query->orderBy(Translation::query()->select('value')
                ->whereColumn('translatable_id', (new Post)->qualifyColumn('id'))
                ->where('translatable_type', (new Post)->getMorphClass())
                ->where('locale', $locale)->where('field', 'title')->orderByDesc('version')->limit(1), $direction);
        }
        $rows = $query->offset(max(0, (int) $request->input('start', 0)))->limit(min(100, max(1, (int) $request->input('length', 25))))->get();
        return response()->json(['ok' => true, 'data' => [
            'draw' => (int) $request->input('draw', 0), 'recordsTotal' => $total, 'recordsFiltered' => $filtered,
            'data' => $rows->map(fn (Post $post) => ['id' => $post->id, 'title' => $post->getTranslation('title', $locale) ?? '', 'media' => $this->featuredImage($post), 'status' => $post->status, 'updated_at' => LocalizedDate::format($post->updated_at, true, $locale), 'translations' => $post->translations->where('field', 'title')->sortByDesc('version')->groupBy('locale')->map(fn ($items) => ['title' => $items->first()?->value])->all(), 'edit_url' => route('admin.posts.edit', ['type' => $type, 'post' => $post->id]), 'delete_url' => route('admin.posts.destroy', ['type' => $type, 'post' => $post->id])]),
        ]]);
    }

    public function create(string $type): View { return $this->form($type, null); }
    public function edit(string $type, int $post): View { return $this->form($type, Post::query()->where('type', $type)->with(['translations', 'files'])->findOrFail($post)); }

    /**
     * Return one post translation for the list's language dialog.
     *
     * @param string $type
     * @param int $post
     * @param string $locale
     *
     * @return JsonResponse
     */
    public function translation(string $type, int $post, string $locale): JsonResponse
    {
        abort_unless(in_array($locale, LocaleCatalog::codes(), true), 404);
        $record = Post::query()->where('type', $type)->findOrFail($post);
        $fields = (array) PostTypeRegistry::getOption($type, 'translation-fields', ['title', 'excerpt', 'content']);

        return response()->json(['ok' => true, 'data' => [
            'fields' => array_intersect_key($record->getTranslations($locale), array_flip($fields)),
            'version' => RecordVersion::token($record, 'posts:'.$type),
        ]]);
    }

    /**
     * Update one language without changing unrelated post fields or locales.
     *
     * @param PostTranslationRequest $request
     * @param string $type
     * @param int $post
     * @param string $locale
     *
     * @return JsonResponse
     */
    public function saveTranslation(PostTranslationRequest $request, string $type, int $post, string $locale): JsonResponse
    {
        $data = $request->validated();
        $fields = (array) $data['fields'];
        $context = ['type' => $type, 'post_id' => $post, 'locale' => $locale, 'user_id' => $request->user()?->getAuthIdentifier(), 'fields' => array_keys($fields)];
        $changed = DB::transaction(function () use ($type, $post, $locale, &$fields, $context, $data): bool {
            $record = Post::query()->where('type', $type)->lockForUpdate()->findOrFail($post);
            abort_unless(hash_equals(RecordVersion::token($record, 'posts:'.$type), (string) $data['_version']), 409, trans('posts.conflict'));
            if (array_key_exists('content', $fields) && $fields['content'] !== null && $fields['content'] !== '') {
                $document = is_string($fields['content']) ? json_decode($fields['content'], true) : $fields['content'];
                $document = Hooks::filter('jobic.editor.content', $document, ['type' => $type, 'locale' => $locale, 'post_id' => $post]);
                $validator = Validator::make(['content' => $document], ['content' => ['required', new EditorContentRule(app(EditorDriverRegistry::class))]]);
                if ($validator->fails()) {
                    throw ValidationException::withMessages(['fields.content' => $validator->errors()->first('content')]);
                }
                $fields['content'] = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $changed = array_filter($fields, static fn ($value, $field): bool => $record->getTranslation((string) $field, $locale) !== $value, ARRAY_FILTER_USE_BOTH);
            if ($changed === []) {
                return false;
            }
            Hooks::action('jobic.posts.saving', $context);
            $historyBefore = $this->historySnapshot($record);
            $before = $record->snapshot();
            $record->translate($locale, $changed);
            $this->syncEditorMedia($record);
            $record->revisions()->create(['snapshot' => $before]);
            Post::withoutEvents(static fn () => $record->touch());
            app(RecordHistory::class)->record('posts:'.$type, $post, (int) $context['user_id'], 'update', $historyBefore, $this->historySnapshot($record->fresh()));
            return true;
        });

        if ($changed) Hooks::notify('jobic.posts.saved', $context);

        return response()->json(['ok' => true, 'message' => trans('posts.translation_saved'), 'data' => ['fields' => $fields]], 200)->header('X-CSRF-TOKEN', csrf_token());
    }

    private function form(string $type, ?Post $post): View
    {
        $this->preparePage('form');
        $builder = PostTypeRegistry::for($type);
        $postType = new Post;
        $postType->type = $type;
        $taxonomyTypes = $postType->taxonomyAllowTypes();
        $activeLocale = LocaleCatalog::resolve((string) request()->query('locale', app()->getLocale()));
        $editorConfig = Hooks::filter('jobic.editor.config', [
            'tools' => Hooks::filter('jobic.editor.tools', Editor::clientTools(), ['type' => $type, 'post_id' => $post?->id]),
            'minHeight' => 280,
        ], ['type' => $type, 'post_id' => $post?->id]);
        $metadata = $post?->getMetadata()?->all() ?? [];
        $taxonomyOptions = [];
        $taxonomyLabels = [];
        foreach ($taxonomyTypes as $collection => $definition) {
            $taxonomyLabels[$collection] = TaxonomyTypeRegistry::for($definition['type'])->getLabel();
            $taxonomyOptions[$collection] = $this->taxonomyOptions($definition['type'], $activeLocale);
        }
        $selectedTaxonomies = [];
        if ($post) {
            foreach ($taxonomyTypes as $collection => $_) {
                $selectedTaxonomies[$collection] = $post->taxonomies()->wherePivot('collection', $collection)->pluck((new Taxonomy)->getTable().'.id')->map(fn ($id) => (int) $id)->all();
            }
        }
        $mediaCollections = $this->mediaCollections($builder->get());
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
        Page::addLocalize('postEditor', [
            'tools' => $editorConfig['tools'],
            'minHeight' => $editorConfig['minHeight'],
            'uploadUrl' => route('admin.media-manager.upload'),
            'messages' => trans('posts.editor'),
            'indexUrl' => route('admin.posts.index', ['type' => $type]),
            'createUrl' => route('admin.posts.create', ['type' => $type]),
            'activeLocale' => $activeLocale,
            'formLabels' => [
                'save' => trans('posts.save'),
                'cancel' => trans('posts.cancel'),
                'save_options' => trans('posts.save_options'),
                'save_close' => trans('posts.save_close'),
                'save_new' => trans('posts.save_new'),
                'unsaved_title' => trans('posts.unsaved_title'),
                'leave' => trans('posts.leave'),
                'revision_title' => trans('posts.revisions'),
                'restore_revision' => trans('posts.restore_revision'),
            ],
        ]);
        return view('posts.form', ['type' => $type, 'typeLabel' => $builder->get()['label'] ?? $type, 'description' => $builder->get()['description'] ?? '', 'urlPrefix' => $builder->get()['url-prefix'] ?? $type, 'hasUrl' => array_key_exists('url-prefix', $builder->get()), 'hasComments' => (bool) ($builder->get()['comments'] ?? false), 'activeLocale' => $activeLocale, 'record' => $post, 'version' => $post ? RecordVersion::token($post, 'posts:'.$type) : null, 'revisions' => $post?->revisions()->get(['id', 'created_at']) ?? collect(), 'locales' => LocaleCatalog::all(), 'translationFields' => $builder->get()['translation-fields'] ?? ['title', 'excerpt', 'content'], 'editorConfig' => $editorConfig, 'customFields' => $builder->get()['custom-fields'] ?? [], 'metadata' => $metadata, 'taxonomyTypes' => $taxonomyTypes, 'taxonomyLabels' => $taxonomyLabels, 'taxonomyOptions' => $taxonomyOptions, 'selectedTaxonomies' => $selectedTaxonomies, 'mediaCollections' => $mediaCollections, 'selectedMedia' => $selectedMedia]);
    }

    /**
     * Register shared CRUD messages and page assets for post screens.
     *
     * @param string $screen
     *
     * @return void
     */
    private function preparePage(string $screen): void
    {
        Page::prepare('posts');
        if ($screen === 'list') {
            \JobMetric\Domi\Facades\Domi::setPlugins(['jquery', 'datatable', 'select2']);
        }
        Page::addLocalize('posts', trans('posts'));
        Page::addLocalize('fileManager', trans('file-manager'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
    }

    /**
     * Resolve the media fields declared by the registered post type.
     *
     * @param array<string, mixed> $definition
     *
     * @return array<string, array<string, mixed>>
     */
    private function mediaCollections(array $definition): array
    {
        return (array) ($definition['media-collections'] ?? []);
    }

    /**
     * Return the URL of the post's featured image for the list thumbnail.
     *
     * @param Post $post
     *
     * @return string|null
     */
    private function featuredImage(Post $post): ?string
    {
        $media = $post->files->first(fn ($file): bool => $file->pivot?->collection === 'base');

        return $media ? (MediaResource::make($media)->resolve(request())['src'] ?? null) : null;
    }

    /**
     * Return a bounded page of changes made to one post.
     *
     * @param Request $request
     * @param string $type
     * @param int $post
     *
     * @return JsonResponse
     */
    public function history(Request $request, string $type, int $post): JsonResponse
    {
        $record = Post::query()->where('type', $type)->findOrFail($post);
        $page = $request->validate(['page' => ['sometimes', 'integer', 'min:1', 'max:100000']])['page'] ?? 1;

        return response()->json(app(RecordHistory::class)->page('posts:'.$type, (int) $record->id, (int) $page));
    }

    /**
     * Snapshot only public post-owned fields for the shared audit history.
     *
     * @param Post|null $post
     *
     * @return array<string, mixed>
     */
    private function historySnapshot(?Post $post): array
    {
        if (! $post) return [];
        $snapshot = $post->snapshot();
        $attributes = $snapshot['attributes'] ?? [];

        return [
            'translation' => $snapshot['translation'] ?? [],
            'metadata' => $snapshot['metadata'] ?? [],
            'taxonomies' => $snapshot['taxonomies'] ?? [],
            'media' => $snapshot['files'] ?? [],
            'status' => $attributes['status'] ?? null,
            'published_at' => $attributes['published_at'] ?? null,
            'comment_status' => $attributes['comment_status'] ?? null,
            'slug' => $attributes['slug'] ?? null,
        ];
    }

    /**
     * Build a localized taxonomy option list, preserving parent-child ordering for hierarchical types.
     *
     * @param string $type
     * @param string $locale
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function taxonomyOptions(string $type, string $locale): array
    {
        $records = Taxonomy::query()->where('type', $type)->where('status', true)->with('translations')->orderBy('ordering')->orderBy('id')->get();
        $name = static fn (Taxonomy $taxonomy): string => $taxonomy->getTranslation('name', $locale) ?? '#'.$taxonomy->id;
        if (! TaxonomyTypeRegistry::for($type)->hasHierarchical()) {
            return $records->map(fn (Taxonomy $taxonomy): array => ['id' => (int) $taxonomy->id, 'name' => $name($taxonomy)])->all();
        }

        $children = $records->groupBy(fn (Taxonomy $taxonomy): int => (int) ($taxonomy->parent_id ?? 0));
        $options = [];
        $visited = [];
        $append = function (int $parentId, int $depth) use (&$append, &$options, &$visited, $children, $name): void {
            foreach ($children->get($parentId, collect()) as $taxonomy) {
                if (isset($visited[$taxonomy->id])) {
                    continue;
                }

                $visited[$taxonomy->id] = true;
                $options[] = ['id' => (int) $taxonomy->id, 'name' => str_repeat('— ', $depth).$name($taxonomy)];
                $append((int) $taxonomy->id, $depth + 1);
            }
        };
        $append(0, 0);

        foreach ($records as $taxonomy) {
            if (! isset($visited[$taxonomy->id])) {
                $options[] = ['id' => (int) $taxonomy->id, 'name' => $name($taxonomy)];
            }
        }

        return $options;
    }

    public function store(PostRequest $request, string $type): JsonResponse { return $this->save($request, $type, null); }
    public function update(PostRequest $request, string $type, int $post): JsonResponse
    {
        return $this->save($request, $type, Post::query()->where('type', $type)->findOrFail($post));
    }

    private function save(PostRequest $request, string $type, ?Post $post): JsonResponse
    {
        $data = $request->validated();
        $post = DB::transaction(function () use ($data, $type, $post, $request): Post {
            if ($post) {
                $post = Post::query()->where('type', $type)->lockForUpdate()->findOrFail($post->id);
                abort_unless(hash_equals(RecordVersion::token($post, 'posts:'.$type), (string) ($data['_version'] ?? '')), 409, trans('posts.conflict'));
            }
            Hooks::action('jobic.posts.saving', ['type' => $type, 'post_id' => $post?->id, 'user_id' => $request->user()?->getAuthIdentifier(), 'fields' => array_keys($data)]);
            $historyBefore = $this->historySnapshot($post);
            $previousSnapshot = $post?->snapshot();
            $lastRevisionId = (int) ($post?->revisions()->max('id') ?? 0);
            $isNew = $post === null;
            $targetStatus = (string) $data['status'];
            $post ??= new Post;
            $post->type = $type;
            $post->fill(array_intersect_key($data, array_flip(['published_at', 'comment_status', 'password', 'slug'])));
            if ($isNew) {
                $startStatus = $post->pickFlow()?->states()->start()->value('status');
                $post->status = $startStatus ?: $targetStatus;
            }
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
            app(PostWorkflow::class)->transition($post, $targetStatus, $request->user(), $isNew);
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

            app(RecordHistory::class)->record('posts:'.$type, (int) $post->id, (int) auth()->id(), $previousSnapshot === null ? 'create' : 'update', $historyBefore, $this->historySnapshot($post));

            return $post;
        });
        Hooks::notify('jobic.posts.saved', ['type' => $type, 'post_id' => $post->id, 'user_id' => $request->user()?->getAuthIdentifier(), 'fields' => array_keys($data)]);
        return response()->json(['ok' => true, 'message' => trans('posts.saved'), 'data' => ['id' => $post->id, 'version' => RecordVersion::token($post->fresh(), 'posts:'.$type), 'edit_url' => route('admin.posts.edit', ['type' => $type, 'post' => $post->id]), 'update_url' => route('admin.posts.update', ['type' => $type, 'post' => $post->id])]], 200)->header('X-CSRF-TOKEN', csrf_token());
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
        DB::transaction(function () use ($record, $type, $request): void {
            $before = $this->historySnapshot($record);
            $record->delete();
            app(RecordHistory::class)->record('posts:'.$type, (int) $record->id, (int) $request->user()->getAuthIdentifier(), 'delete', $before, []);
        });
        Hooks::notify('jobic.posts.deleted', ['type' => $type, 'post_id' => $post, 'user_id' => $request->user()?->getAuthIdentifier()]);
        return response()->json(['ok' => true, 'message' => trans('posts.deleted')]);
    }

    /**
     * Apply one authorized operation atomically to a bounded set of posts.
     *
     * @param BulkPostRequest $request
     * @param string $type
     *
     * @return JsonResponse
     */
    public function bulk(BulkPostRequest $request, string $type): JsonResponse
    {
        $data = $request->validated();
        $records = Post::query()->where('type', $type)->whereIn('id', $data['ids'])->get();
        if ($records->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => [trans('posts.selection_invalid')]]);
        }

        DB::transaction(function () use ($records, $data, $type, $request): void {
            foreach ($records as $record) {
                $context = ['type' => $type, 'post_id' => $record->id, 'user_id' => $request->user()?->getAuthIdentifier()];
                $before = $this->historySnapshot($record);
                if ($data['operation'] === 'delete') {
                    Hooks::action('jobic.posts.deleting', $context);
                    $record->delete();
                    app(RecordHistory::class)->record('posts:'.$type, (int) $record->id, (int) $context['user_id'], 'delete', $before, []);
                    continue;
                }

                Hooks::action('jobic.posts.saving', [...$context, 'fields' => ['status']]);
                app(PostWorkflow::class)->transition($record, $data['operation'] === 'activate' ? 'publish' : 'disabled', $request->user());
                app(RecordHistory::class)->record('posts:'.$type, (int) $record->id, (int) $context['user_id'], 'update', $before, $this->historySnapshot($record));
            }
        });

        foreach ($records as $record) {
            $context = ['type' => $type, 'post_id' => $record->id, 'user_id' => $request->user()?->getAuthIdentifier()];
            if ($data['operation'] === 'delete') {
                Hooks::notify('jobic.posts.deleted', $context);
            } else {
                Hooks::notify('jobic.posts.saved', [...$context, 'fields' => ['status']]);
            }
        }

        return response()->json(['ok' => true, 'message' => trans('posts.bulk_done'), 'data' => ['count' => $records->count()]], 200)->header('X-CSRF-TOKEN', csrf_token());
    }

    public function restoreRevision(Request $request, string $type, int $post, int $revision): JsonResponse
    {
        $record = Post::query()->where('type', $type)->findOrFail($post);
        $record->revisions()->findOrFail($revision);
        Hooks::action('jobic.posts.revision.restoring', ['type' => $type, 'post_id' => $record->id, 'revision_id' => $revision, 'user_id' => $request->user()?->getAuthIdentifier()]);
        DB::transaction(function () use ($record, $revision, $type, $request): void {
            $before = $this->historySnapshot($record);
            $record->restoreRevision($revision);
            app(RecordHistory::class)->record('posts:'.$type, (int) $record->id, (int) $request->user()->getAuthIdentifier(), 'restore', $before, $this->historySnapshot($record->fresh()));
        });
        Hooks::notify('jobic.posts.revision.restored', ['type' => $type, 'post_id' => $record->id, 'revision_id' => $revision, 'user_id' => $request->user()?->getAuthIdentifier()]);
        return response()->json(['ok' => true, 'message' => trans('posts.revision_restored')]);
    }
}
