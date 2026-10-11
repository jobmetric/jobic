<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Facades\Editor;
use App\Facades\Hooks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\BulkPostRequest;
use App\Http\Requests\Posts\PostRequest;
use App\Http\Requests\Posts\PostTranslationRequest;
use App\Http\Requests\Taxonomies\UnlockEditLocksRequest;
use App\Rules\EditorContentRule;
use App\Services\History\RecordHistory;
use App\Services\Posts\Posts;
use App\Services\Posts\PostWorkflow;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\AdminAccess;
use App\Support\EditorDriverRegistry;
use App\Support\LocaleCatalog;
use App\Support\LocalizedDate;
use App\Support\Page;
use App\Support\RecordVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Media\Http\Resources\MediaResource;
use JobMetric\Metadata\Models\Meta;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Support\PostTypeBuilder;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;
use JobMetric\Translation\Models\Translation;
use Throwable;

final class PostController extends Controller
{
    /**
     * Render the registry-driven post list and localized page options.
     *
     * @param string $type
     *
     * @return View
     * @throws Throwable
     */
    public function index(string $type): View
    {
        $builder = PostTypeRegistry::for($type);

        $this->preparePage('list');

        $postType = new Post;
        $postType->type = $type;
        $statusLabels = app(PostWorkflow::class)->allStatusOptions($postType, LocaleCatalog::resolve(app()->getLocale())) ?: trans('posts.statuses');

        $listFilters = $this->listFilters($builder, $type, $statusLabels);

        $languages = collect(LocaleCatalog::all())
            ->map(fn (array $language, string $locale): array => [
                'name'      => $language['name'] ?? strtoupper($locale),
                'flag_url'  => LocaleCatalog::flagUrl($language['flag'] ?? null),
                'direction' => $language['direction'] ?? 'auto',
            ])
            ->all();

        $mediaCollections = $builder->getMediaCollections();
        $operations = ['delete'];
        $context = ['type' => $type];

        $canUnlockEditLocks = $builder->hasOptimisticLocking() && app(AdminAccess::class)->allows(auth()->user(), (string) config('admin.permissions.unlock_edit_lock', 'admin.unlock_edit_lock'));

        if ($builder->hasChangeStatusInList()) {
            array_unshift($operations, 'activate', 'deactivate');
        }

        if ($builder->hasHelp()) {
            $operations[] = 'help';
        }

        if ($canUnlockEditLocks) {
            $operations[] = 'unlock';
        }

        Page::addLocalize('postPage', [
            'type'               => $type,
            'dataUrl'            => route('admin.posts.data', [
                'type' => $type,
            ]),
            'createUrl'          => route('admin.posts.create', [
                'type' => $type,
            ]),
            'bulkUrl'            => route('admin.posts.bulk', [
                'type' => $type,
            ]),
            'activeLocale'       => LocaleCatalog::resolve(app()->getLocale()),
            'languages'          => $languages,
            'hasMedia'           => $mediaCollections !== [],
            'statuses'           => $statusLabels,
            'operations'         => $operations,
            'canUnlockEditLocks' => $canUnlockEditLocks,
            'editLockUnlockUrl'  => route('admin.posts.edit-locks.unlock', [
                'type' => $type,
            ]),
            'editLockItemUrl'    => route('admin.posts.edit-lock.unlock', [
                'type' => $type,
                'post' => '__ID__',
            ]),
            'helpTitle'          => trans($type === 'page' ? 'posts.page_help_title' : 'posts.help_title'),
            'help'               => Hooks::filter('jobic.posts.help.items', trans($type === 'page' ? 'posts.page_help_items' : 'posts.help_items'), $context),
            'helpIntro'          => Hooks::filter('jobic.posts.help.intro', trans($type === 'page' ? 'posts.page_help_intro' : 'posts.help_intro'), $context),
            'backUrl'            => $builder->getBackUrl(),
            'translationFields'  => array_values(array_diff($builder->getTranslationFields(), [
                'meta_title',
                'meta_description',
                'meta_keywords',
            ])),
            'translationUrl'     => route('admin.posts.translation', [
                'type'   => $type,
                'post'   => '__ID__',
                'locale' => '__LOCALE__',
            ]),
            'translationSaveUrl' => route('admin.posts.translation.update', [
                'type'   => $type,
                'post'   => '__ID__',
                'locale' => '__LOCALE__',
            ]),
            'editor'             => [
                'tools'     => Hooks::filter('jobic.editor.tools', Editor::clientTools(), [
                    'type'    => $type,
                    'post_id' => null,
                ]),
                'uploadUrl' => route('admin.media-manager.upload'),
                'messages'  => trans('posts.editor'),
            ],
        ]);

        return view('posts.index', [
            'type'         => $type,
            'typeLabel'    => $builder->getLabel() ?: $type,
            'description'  => $builder->getDescription(),
            'backUrl'      => $builder->getBackUrl(),
            'languages'    => $languages,
            'activeLocale' => LocaleCatalog::resolve(app()->getLocale()),
            'hasMedia'     => $mediaCollections !== [],
            'statusLabels' => $statusLabels,
            'listFilters'  => $listFilters,
        ]);
    }

    /**
     * Return a bounded, filtered page of posts for the administration table.
     *
     * @param Request $request
     * @param string $type
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function data(Request $request, string $type): JsonResponse
    {
        $search = trim((string) $request->input('search.value', ''));
        $locale = LocaleCatalog::resolve(app()->getLocale());

        $query = Post::query()
            ->where('type', $type)
            ->with(['translations', 'files']);

        $total = (clone $query)->count();
        $filters = (array) $request->input('filters', []);

        foreach ($this->listFilters(PostTypeRegistry::for($type), $type, [], false) as $key => $filter) {
            $value = $filters[$key] ?? ($key === 'status' ? $request->input('status') : null);
            if (! is_scalar($value) || (string) $value === '') {
                continue;
            }

            if ($filter['kind'] === 'taxonomy') {
                if (! ctype_digit((string) $value) || ! Taxonomy::query()
                        ->whereKey((int) $value)
                        ->where('type', $filter['type'])
                        ->where('status', true)
                        ->exists()) {
                    $query->whereRaw('1 = 0');

                    continue;
                }

                $relationTable = config('taxonomy.tables.taxonomy_relation', 'taxonomy_relations');
                $query->whereHas('taxonomies', fn ($taxonomy) => $taxonomy->whereKey((int) $value)
                    ->where($relationTable . '.collection', $filter['collection']));
            } else {
                if ($filter['kind'] === 'status') {
                    if (array_key_exists((string) $value, $filter['options'])) {
                        $query->where('status', (string) $value);
                    }
                } else {
                    if ($filter['kind'] === 'metadata') {
                        $query->whereHas('metas', fn ($meta) => $meta->where('key', $filter['field'])
                            ->where('value', (string) $value));
                    } else {
                        if (array_key_exists((string) $value, $filter['options']) && is_callable($filter['apply'] ?? null)) {
                            ($filter['apply'])($query, (string) $value, $request);
                        }
                    }
                }
            }
        }
        if ($search !== '') {
            $query->whereHas('translations', fn ($translation) => $translation->where('locale', $locale)
                ->where('field', 'title')
                ->where('value', 'like', "%{$search}%"));
        }

        $filtered = (clone $query)->count();
        $sortColumn = (int) $request->input('order.0.column', 1);
        $direction = strtolower((string) $request->input('order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if ($sortColumn === 1) {
            $query->orderBy(Translation::query()
                ->select('value')
                ->whereColumn('translatable_id', (new Post)->qualifyColumn('id'))
                ->where('translatable_type', (new Post)->getMorphClass())
                ->where('locale', $locale)
                ->where('field', 'title')
                ->orderByDesc('version')
                ->limit(1), $direction);
        } else {
            if ($sortColumn === 2) {
                $query->orderBy('status', $direction);
            } else {
                if ($sortColumn === 3) {
                    $query->orderBy('updated_at', $direction)
                        ->orderBy('id', $direction);
                } else {
                    $query->orderBy(Translation::query()
                        ->select('value')
                        ->whereColumn('translatable_id', (new Post)->qualifyColumn('id'))
                        ->where('translatable_type', (new Post)->getMorphClass())
                        ->where('locale', $locale)
                        ->where('field', 'title')
                        ->orderByDesc('version')
                        ->limit(1), $direction);
                }
            }
        }
        $rows = $query->offset(max(0, (int) $request->input('start', 0)))
            ->limit(min(100, max(1, (int) $request->input('length', 25))))
            ->get();

        return response()->json([
            'ok'   => true,
            'data' => [
                'draw'            => (int) $request->input('draw', 0),
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtered,
                'data'            => $rows->map(fn (Post $post) => [
                    'id'           => $post->id,
                    'title'        => $post->getTranslation('title', $locale) ?? '',
                    'media'        => $this->featuredImage($post),
                    'status'       => $post->status,
                    'updated_at'   => LocalizedDate::format($post->updated_at, true, $locale),
                    'translations' => $post->translations->where('field', 'title')
                        ->sortByDesc('version')
                        ->groupBy('locale')
                        ->map(fn ($items) => ['title' => $items->first()?->value])
                        ->all(),
                    'edit_lock'    => $this->editLockDetails($type, (int) $post->id),
                    'edit_url'     => route('admin.posts.edit', [
                        'type' => $type,
                        'post' => $post->id,
                    ]),
                    'delete_url'   => route('admin.posts.destroy', [
                        'type' => $type,
                        'post' => $post->id,
                    ]),
                ]),
            ],
        ]);
    }

    /**
     * Render an empty form for a registered post type.
     *
     * @param string $type
     *
     * @return View|RedirectResponse
     * @throws Throwable
     */
    public function create(string $type): View|RedirectResponse
    {
        return $this->form($type, null);
    }

    /**
     * Render the edit form for a post belonging to the requested type.
     *
     * @param string $type
     * @param int $post
     *
     * @return View|RedirectResponse
     * @throws Throwable
     */
    public function edit(string $type, int $post): View|RedirectResponse
    {
        return $this->form($type, Post::query()
            ->where('type', $type)
            ->with(['translations', 'files'])
            ->findOrFail($post));
    }

    /**
     * Return one post translation for the list's language dialog.
     *
     * @param string $type
     * @param int $post
     * @param string $locale
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function translation(string $type, int $post, string $locale): JsonResponse
    {
        abort_unless(in_array($locale, LocaleCatalog::codes(), true), 404);

        $record = Post::query()
            ->where('type', $type)
            ->findOrFail($post);
        $fields = PostTypeRegistry::for($type)
            ->getTranslationFields();

        return response()->json([
            'ok'   => true,
            'data' => [
                'fields'  => array_intersect_key($record->getTranslations($locale), array_flip($fields)),
                'version' => PostTypeRegistry::for($type)
                    ->hasOptimisticLocking() ? RecordVersion::token($record, 'posts:' . $type) : null,
            ],
        ]);
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
     * @throws Throwable
     */
    public function saveTranslation(PostTranslationRequest $request, string $type, int $post, string $locale): JsonResponse
    {
        $this->assertEditLockAvailable($type, $post, (int) $request->user()->id);

        $data = $request->validated();
        $fields = (array) $data['fields'];

        $context = [
            'type'    => $type,
            'post_id' => $post,
            'locale'  => $locale,
            'user_id' => $request->user()
                ?->getAuthIdentifier(),
            'fields'  => array_keys($fields),
        ];

        $changed = DB::transaction(function () use ($type, $post, $locale, &$fields, $context, $data): bool {
            $record = Post::query()
                ->where('type', $type)
                ->lockForUpdate()
                ->findOrFail($post);

            if (PostTypeRegistry::for($type)
                ->hasOptimisticLocking()) {
                abort_unless(hash_equals(RecordVersion::token($record, 'posts:' . $type), (string) $data['_version']), 409, trans('posts.conflict'));
            }

            if (array_key_exists('content', $fields) && $fields['content'] !== null && $fields['content'] !== '') {
                $document = is_string($fields['content']) ? json_decode($fields['content'], true) : $fields['content'];
                $document = Hooks::filter('jobic.editor.content', $document, ['type' => $type, 'locale' => $locale, 'post_id' => $post]);
                $validator = Validator::make([
                    'content' => $document,
                ], [
                    'content' => [
                        'required',
                        new EditorContentRule(app(EditorDriverRegistry::class)),
                    ],
                ]);

                if ($validator->fails()) {
                    throw ValidationException::withMessages([
                        'fields.content' => $validator->errors()
                            ->first('content'),
                    ]);
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
            $record->revisions()
                ->create(['snapshot' => $before]);

            Post::withoutEvents(static fn () => $record->touch());

            app(RecordHistory::class)->record('posts:' . $type, $post, (int) $context['user_id'], 'update', $historyBefore, $this->historySnapshot($record->fresh()));

            return true;
        });

        if ($changed) {
            Hooks::notify('jobic.posts.saved', $context);
        }

        return response()
            ->json([
                'ok'      => true,
                'message' => trans('posts.translation_saved'),
                'data'    => [
                    'fields' => $fields,
                ],
            ], 200)
            ->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Prepare the active-language form and registered post capabilities.
     *
     * @param string $type
     * @param Post|null $post
     *
     * @return View|RedirectResponse
     * @throws Throwable
     */
    private function form(string $type, ?Post $post): View|RedirectResponse
    {
        $this->preparePage('form');

        $builder = PostTypeRegistry::for($type);

        $editLock = null;

        if ($post && $builder->hasOptimisticLocking()) {
            $lease = app(TaxonomyEditLocks::class)->acquire('posts:' . $type, (int) $post->id, request()->user());
            if (! $lease['acquired']) {
                $lock = $lease['lock'] ?? [];

                return redirect()
                    ->route('admin.posts.index', ['type' => $type])
                    ->with('error', trans('posts.edit_locked', [
                        'user' => $lock['user_name'] ?? trans('taxonomies.unknown_editor'),
                        'time' => LocalizedDate::format($lock['locked_at'] ?? null),
                    ]));
            }
            $editLock = $lease['lock'];
        }

        $postType = new Post;
        $postType->type = $type;
        $taxonomyTypes = $postType->taxonomyAllowTypes();
        $activeLocale = LocaleCatalog::resolve(app()->getLocale());

        $editorConfig = Hooks::filter('jobic.editor.config', [
            'tools'     => Hooks::filter('jobic.editor.tools', Editor::clientTools(), [
                'type'    => $type,
                'post_id' => $post?->id,
            ]),
            'minHeight' => 280,
        ], [
            'type'    => $type,
            'post_id' => $post?->id,
        ]);

        $metadata = $post?->getMetadata()
            ?->all() ?? [];

        $taxonomyOptions = [];
        $taxonomyLabels = [];

        foreach ($taxonomyTypes as $collection => $definition) {
            $taxonomyLabels[$collection] = TaxonomyTypeRegistry::for($definition['type'])
                ->getLabel();
            $taxonomyOptions[$collection] = $this->taxonomyOptions($definition['type'], $activeLocale);
        }

        $selectedTaxonomies = [];
        if ($post) {
            foreach ($taxonomyTypes as $collection => $_) {
                $selectedTaxonomies[$collection] = $post->taxonomies()
                    ->wherePivot('collection', $collection)
                    ->pluck((new Taxonomy)->getTable() . '.id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            }
        }

        $mediaCollections = $builder->getMediaCollections();

        $selectedMedia = [];
        if ($post) {
            foreach ($mediaCollections as $collection => $_) {
                $selectedMedia[$collection] = $post->getMediaByCollection($collection)
                    ->get()
                    ->map(function ($media) {
                        $item = MediaResource::make($media)
                            ->resolve(request());
                        $item['url'] = $item['src'] ?? null;
                        $item['thumbnail_url'] = $item['src'] ?? null;
                        $item['group'] = $item['mime_group'] ?? 'document';

                        return $item;
                    })
                    ->all();
            }
        }

        Page::addLocalize('postEditor', [
            'tools'        => $editorConfig['tools'],
            'minHeight'    => $editorConfig['minHeight'],
            'uploadUrl'    => route('admin.media-manager.upload'),
            'messages'     => trans('posts.editor'),
            'indexUrl'     => route('admin.posts.index', [
                'type' => $type,
            ]),
            'createUrl'    => route('admin.posts.create', [
                'type' => $type,
            ]),
            'activeLocale' => $activeLocale,
            'calendar'     => LocaleCatalog::calendar($activeLocale),
            'formLabels'   => [
                'save'             => trans('posts.save'),
                'cancel'           => trans('posts.cancel'),
                'save_options'     => trans('posts.save_options'),
                'save_close'       => trans('posts.save_close'),
                'save_new'         => trans($type === 'page' ? 'posts.save_new_page' : 'posts.save_new'),
                'unsaved_title'    => trans('posts.unsaved_title'),
                'leave'            => trans('posts.leave'),
                'revision_title'   => trans('posts.revisions'),
                'restore_revision' => trans('posts.restore_revision'),
            ],
        ]);
        $editorConfig['indexUrl'] = $builder->getBackUrl() ?: route('admin.posts.index', ['type' => $type]);

        return view('posts.form', [
            'type'               => $type,
            'typeLabel'          => $builder->getLabel() ?: $type,
            'description'        => $builder->getDescription(),
            'backUrl'            => $builder->getBackUrl(),
            'urlPrefix'          => $builder->getUrlPrefix($type),
            'hasUrl'             => $builder->hasUrl(),
            'hasComments'        => $builder->hasComments(),
            'hasSeo'             => $builder->isSeoEnabled(),
            'activeLocale'       => $activeLocale,
            'record'             => $post,
            'editLock'           => $editLock,
            'editLockRenewUrl'   => $post ? route('admin.posts.edit-lock.renew', [
                'type' => $type,
                'post' => $post->id,
            ]) : null,
            'editLockReleaseUrl' => $post ? route('admin.posts.edit-lock.release', [
                'type' => $type,
                'post' => $post->id,
            ]) : null,
            'version'            => $post && $builder->hasOptimisticLocking() ? RecordVersion::token($post, 'posts:' . $type) : null,
            'revisions'          => $post?->revisions()
                    ->latest('id')
                    ->get([
                        'id',
                        'created_at',
                        'snapshot',
                    ]) ?? collect(),
            'locales'            => LocaleCatalog::all(),
            'translationFields'  => $builder->getTranslationFields(),
            'editorConfig'       => $editorConfig,
            'customFields'       => $builder->getCustomFields(),
            'metadata'           => $metadata,
            'taxonomyTypes'      => $taxonomyTypes,
            'taxonomyLabels'     => $taxonomyLabels,
            'taxonomyOptions'    => $taxonomyOptions,
            'selectedTaxonomies' => $selectedTaxonomies,
            'mediaCollections'   => $mediaCollections,
            'selectedMedia'      => $selectedMedia,
        ]);
    }

    /**
     * Return the current edit lock for one post, if any.
     *
     * @param string $type
     * @param int $id
     *
     * @return array|null
     */
    private function editLockDetails(string $type, int $id): ?array
    {
        if (! PostTypeRegistry::for($type)
            ->hasOptimisticLocking()) {
            return null;
        }

        $lock = app(TaxonomyEditLocks::class)->inspect('posts:' . $type, $id);

        if (! $lock) {
            return null;
        }

        return [
            'user_id'   => (int) ($lock['user_id'] ?? 0),
            'user_name' => (string) ($lock['user_name'] ?? trans('taxonomies.unknown_editor')),
            'locked_at' => LocalizedDate::format($lock['locked_at'] ?? null),
            'owned'     => (int) ($lock['user_id'] ?? 0) === (int) auth()->id(),
        ];
    }

    /**
     * Register shared CRUD messages and page assets for post screens.
     *
     * @param string $screen
     *
     * @return void
     * @throws SetPluginNotFoundException
     */
    private function preparePage(string $screen): void
    {
        Page::prepare('posts');

        if ($screen === 'list') {
            Page::addPlugin('datatable');
        } else {
            Page::addPlugin(in_array(LocaleCatalog::calendar(), ['jalali', 'persian'], true) ? 'datepicker.jalali' : 'datepicker');
        }

        Page::addLocalize('posts', trans('posts'));
        Page::addLocalize('fileManager', trans('file-manager'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
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

        return $media ? (MediaResource::make($media)
                             ->resolve(request())['src'] ?? null) : null;
    }

    /**
     * Return a bounded page of changes made to one post.
     *
     * @param Request $request
     * @param string $type
     * @param int $post
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function history(Request $request, string $type, int $post): JsonResponse
    {
        $record = Post::query()
            ->where('type', $type)
            ->findOrFail($post);

        $page = $request->validate([
            'page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100000',
            ],
        ])['page'] ?? 1;

        return response()->json(app(RecordHistory::class)->page('posts:' . $type, (int) $record->id, (int) $page));
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
        if (! $post) {
            return [];
        }

        $snapshot = $post->snapshot();
        $attributes = $snapshot['attributes'] ?? [];

        return [
            'translation'    => $snapshot['translation'] ?? [],
            'metadata'       => $snapshot['metadata'] ?? [],
            'taxonomies'     => $snapshot['taxonomies'] ?? [],
            'media'          => $snapshot['files'] ?? [],
            'status'         => $attributes['status'] ?? null,
            'published_at'   => $attributes['published_at'] ?? null,
            'comment_status' => $attributes['comment_status'] ?? null,
            'slug'           => $attributes['slug'] ?? null,
        ];
    }

    /**
     * Build a localized taxonomy option list, preserving parent-child ordering for hierarchical types.
     *
     * @param string $type
     * @param string $locale
     *
     * @return array<int, array{id: int, name: string, depth: int, parent_id: int|null}>
     */
    private function taxonomyOptions(string $type, string $locale): array
    {
        $records = Taxonomy::query()
            ->where('type', $type)
            ->where('status', true)
            ->with('translations')
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();

        $name = static fn (Taxonomy $taxonomy): string => $taxonomy->getTranslation('name', $locale) ?? '#' . $taxonomy->id;

        if (! TaxonomyTypeRegistry::for($type)
            ->hasHierarchical()) {

            return $records->map(fn (Taxonomy $taxonomy): array => [
                'id'        => (int) $taxonomy->id,
                'name'      => $name($taxonomy),
                'depth'     => 0,
                'parent_id' => null,
            ])
                ->all();
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
                $options[] = [
                    'id'        => (int) $taxonomy->id,
                    'name'      => $name($taxonomy),
                    'depth'     => $depth,
                    'parent_id' => $taxonomy->parent_id ? (int) $taxonomy->parent_id : null,
                ];
                $append((int) $taxonomy->id, $depth + 1);
            }
        };
        $append(0, 0);

        foreach ($records as $taxonomy) {
            if (! isset($visited[$taxonomy->id])) {
                $options[] = [
                    'id'        => (int) $taxonomy->id,
                    'name'      => $name($taxonomy),
                    'depth'     => 0,
                    'parent_id' => $taxonomy->parent_id ? (int) $taxonomy->parent_id : null,
                ];
            }
        }

        return $options;
    }

    /**
     * Build taxonomy and extension filters registered for one post type.
     *
     * @param PostTypeBuilder $builder
     * @param string $type
     * @param array $statusLabels
     * @param bool $includeOptions
     *
     * @return array
     */
    private function listFilters(PostTypeBuilder $builder, string $type, array $statusLabels = [], bool $includeOptions = true): array
    {
        $locale = LocaleCatalog::resolve(app()->getLocale());

        $filters = [];
        foreach ($builder->getFilterFields() as $field) {
            if ($field === 'status') {
                if ($statusLabels === []) {
                    $postType = new Post;
                    $postType->type = $type;
                    $statusLabels = app(PostWorkflow::class)->allStatusOptions($postType, LocaleCatalog::resolve(app()->getLocale())) ?: trans('posts.statuses');
                }
                $filters[$field] = ['kind' => 'status', 'label' => 'posts.status', 'options' => $statusLabels];

                continue;
            }

            if (str_starts_with($field, 'taxonomy.')) {
                $taxonomyType = substr($field, strlen('taxonomy.'));
                $collection = collect($builder->getTaxonomyTypes())
                    ->filter(fn (array $definition): bool => ($definition['type'] ?? null) === $taxonomyType)
                    ->keys()
                    ->first();

                if ($collection === null || ! TaxonomyTypeRegistry::has($taxonomyType)) {
                    continue;
                }

                $filters[$field] = [
                    'kind'       => 'taxonomy',
                    'type'       => $taxonomyType,
                    'collection' => $collection,
                    'label'      => TaxonomyTypeRegistry::for($taxonomyType)
                        ->getLabel(),
                    'options'    => $includeOptions ? collect($this->taxonomyOptions($taxonomyType, $locale))
                        ->mapWithKeys(fn ($term) => [
                            (string) $term['id'] => str_repeat('— ', $term['depth']) . $term['name'],
                        ])
                        ->all() : [],
                ];

                continue;
            }
            if (str_starts_with($field, 'metadata.')) {
                $key = substr($field, strlen('metadata.'));
                $allowed = $builder->getMetadataFields();

                if (! in_array('*', $allowed, true) && ! in_array($key, $allowed, true)) {
                    continue;
                }

                $label = $builder->getCustomFields()[$key]['label'] ?? $key;
                $values = $includeOptions ? Meta::query()
                    ->where('metaable_type', (new Post)->getMorphClass())
                    ->where('key', $key)
                    ->whereIn('metaable_id', Post::query()
                        ->where('type', $type)
                        ->select('id'))
                    ->where('is_json', false)
                    ->whereNotNull('value')
                    ->distinct()
                    ->orderBy('value')
                    ->limit(100)
                    ->pluck('value')
                    ->mapWithKeys(fn ($value): array => [(string) $value => (string) $value])
                    ->all() : [];

                $filters[$field] = [
                    'kind'    => 'metadata',
                    'field'   => $key,
                    'label'   => $label,
                    'options' => $values,
                ];
            }
        }
        foreach ($builder->getFilters() as $key => $filter) {
            if (! is_array($filter) || ! isset($filter['label'], $filter['options']) || ! is_array($filter['options'])) {
                continue;
            }

            $filters[$key] = [
                'kind'    => 'custom',
                'label'   => $filter['label'],
                'options' => $filter['options'],
                'apply'   => $filter['apply'] ?? null,
            ];
        }

        return $filters;
    }

    /**
     * Create a post from validated administration input.
     *
     * @param PostRequest $request
     * @param string $type
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(PostRequest $request, string $type): JsonResponse
    {
        return $this->save($request, $type, null);
    }

    /**
     * Update a type-scoped post from validated administration input.
     *
     * @param PostRequest $request
     * @param string $type
     * @param int $post
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(PostRequest $request, string $type, int $post): JsonResponse
    {
        return $this->save($request, $type, Post::query()
            ->where('type', $type)
            ->findOrFail($post));
    }

    /**
     * Renew the current editor's post or page lease.
     *
     * @param Request $request
     * @param string $type
     * @param int $post
     *
     * @return JsonResponse
     */
    public function renewEditLock(Request $request, string $type, int $post): JsonResponse
    {
        Post::query()
            ->where('type', $type)
            ->findOrFail($post);

        $token = (string) $request->input('_edit_lock', '');
        $renewed = $token !== '' && app(TaxonomyEditLocks::class)->refresh('posts:' . $type, $post, (int) $request->user()->id, $token);

        return response()->json([
            'ok'      => $renewed,
            'message' => $renewed ? trans('posts.edit_lock_refreshed') : trans('posts.edit_lock_lost'),
        ], $renewed ? 200 : 423);
    }

    /**
     * Release the current editor's post or page lease.
     *
     * @param Request $request
     * @param string $type
     * @param int $post
     *
     * @return JsonResponse
     */
    public function releaseEditLock(Request $request, string $type, int $post): JsonResponse
    {
        Post::query()
            ->where('type', $type)
            ->findOrFail($post);

        $token = (string) $request->input('_edit_lock', '');
        $released = $token !== '' && app(TaxonomyEditLocks::class)->release('posts:' . $type, $post, (int) $request->user()->id, $token);

        return response()->json([
            'ok'      => $released,
            'message' => $released ? trans('posts.edit_lock_released') : trans('posts.edit_lock_lost'),
        ], $released ? 200 : 423);
    }

    /**
     * Forcefully release a post or page lease, regardless of the current editor.
     *
     * @param string $type
     * @param int $post
     *
     * @return JsonResponse
     */
    public function unlockEditLock(string $type, int $post): JsonResponse
    {
        Post::query()
            ->where('type', $type)
            ->findOrFail($post);
        $lock = app(TaxonomyEditLocks::class)->forceRelease('posts:' . $type, $post);

        return response()->json([
            'ok'      => true,
            'message' => $lock ? trans('posts.edit_lock_released') : trans('posts.edit_lock_already_released'),
        ]);
    }

    /**
     * Release multiple post or page leases in one request.
     *
     * @param UnlockEditLocksRequest $request
     * @param string $type
     *
     * @return JsonResponse
     */
    public function unlockEditLocks(UnlockEditLocksRequest $request, string $type): JsonResponse
    {
        $ids = array_map('intval', $request->validated('ids'));
        $existing = Post::query()
            ->where('type', $type)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        abort_unless(count($existing) === count(array_unique($ids)), 404);

        $locks = app(TaxonomyEditLocks::class);

        $released = 0;
        foreach ($existing as $id) {
            if ($locks->inspect('posts:' . $type, $id) !== null) {
                $locks->forceRelease('posts:' . $type, $id);
                $released++;
            }
        }

        return response()->json([
            'ok'      => true,
            'message' => trans('posts.edit_locks_released', [
                'count' => $released,
            ]),
            'data'    => [
                'released' => $released,
            ],
        ]);
    }

    /**
     * Delegate a validated form write to the CMS post service.
     *
     * @param PostRequest $request
     * @param string $type
     * @param Post|null $post
     *
     * @return JsonResponse
     * @throws Throwable
     */
    private function save(PostRequest $request, string $type, ?Post $post): JsonResponse
    {
        $result = app(Posts::class)->save($type, $request->validated(), $post, $request->user());

        $post = $result->data['post'];
        $editLock = null;
        if (PostTypeRegistry::for($type)
            ->hasOptimisticLocking()) {
            $editLock = app(TaxonomyEditLocks::class)->inspect('posts:' . $type, (int) $post->id);

            if (! $editLock) {
                $lease = app(TaxonomyEditLocks::class)->acquire('posts:' . $type, (int) $post->id, $request->user());
                $editLock = $lease['lock'] ?? null;
            }
        }

        return response()
            ->json([
                'ok'      => $result->ok,
                'message' => $result->message,
                'data'    => [
                    'id'                    => $post->id,
                    'version'               => PostTypeRegistry::for($type)
                        ->hasOptimisticLocking() ? RecordVersion::token($post->fresh(), 'posts:' . $type) : null,
                    'edit_url'              => route('admin.posts.edit', [
                        'type' => $type,
                        'post' => $post->id,
                    ]),
                    'update_url'            => route('admin.posts.update', [
                        'type' => $type,
                        'post' => $post->id,
                    ]),
                    'edit_lock'             => $editLock['token'] ?? null,
                    'edit_lock_renew_url'   => route('admin.posts.edit-lock.renew', [
                        'type' => $type,
                        'post' => $post->id,
                    ]),
                    'edit_lock_release_url' => route('admin.posts.edit-lock.release', [
                        'type' => $type,
                        'post' => $post->id,
                    ]),
                ],
            ], $result->status)
            ->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Synchronize Editor.js media usage through the CMS adapter.
     *
     * @param Post $post
     *
     * @return void
     * @throws Throwable
     */
    private function syncEditorMedia(Post $post): void
    {
        app(Posts::class)->syncEditorMedia($post);
    }

    /**
     * Delete a post while recording CMS history and lifecycle hooks.
     *
     * @param Request $request
     * @param string $type
     * @param int $post
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function destroy(Request $request, string $type, int $post): JsonResponse
    {
        $this->assertEditLockAvailable($type, $post, (int) $request->user()->id);
        $record = Post::query()
            ->where('type', $type)
            ->findOrFail($post);

        Hooks::action('jobic.posts.deleting', [
            'type'    => $type,
            'post_id' => $record->id,
            'user_id' => $request->user()
                ?->getAuthIdentifier(),
        ]);

        DB::transaction(function () use ($record, $type, $request): void {
            $before = $this->historySnapshot($record);
            $record->delete();

            app(RecordHistory::class)->record('posts:' . $type, (int) $record->id, (int) $request->user()
                ->getAuthIdentifier(), 'delete', $before, []);
        });

        app(TaxonomyEditLocks::class)->forceRelease('posts:' . $type, (int) $record->id);

        Hooks::notify('jobic.posts.deleted', [
            'type'    => $type,
            'post_id' => $post,
            'user_id' => $request->user()
                ?->getAuthIdentifier(),
        ]);

        return response()->json(['ok' => true, 'message' => trans('posts.deleted')]);
    }

    /**
     * Apply one authorized operation atomically to a bounded set of posts.
     *
     * @param BulkPostRequest $request
     * @param string $type
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function bulk(BulkPostRequest $request, string $type): JsonResponse
    {
        $data = $request->validated();
        if ($data['operation'] === 'unlock') {
            $existing = Post::query()
                ->where('type', $type)
                ->whereIn('id', $data['ids'])
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            abort_unless(count($existing) === count($data['ids']), 404);

            $locks = app(TaxonomyEditLocks::class);

            $released = 0;
            foreach ($existing as $id) {
                if ($locks->inspect('posts:' . $type, $id) !== null) {
                    $locks->forceRelease('posts:' . $type, $id);
                    $released++;
                }
            }

            return response()->json([
                'ok'      => true,
                'message' => trans('posts.edit_locks_released', [
                    'count' => $released,
                ]),
                'data'    => [
                    'released' => $released,
                ],
            ]);
        }

        abort_if(in_array($data['operation'], ['activate', 'deactivate'], true) && ! PostTypeRegistry::for($type)
                ->hasChangeStatusInList(), 403);

        $records = Post::query()
            ->where('type', $type)
            ->whereIn('id', $data['ids'])
            ->get();

        if ($records->count() !== count($data['ids'])) {
            throw ValidationException::withMessages([
                'ids' => [trans('posts.selection_invalid')],
            ]);
        }

        foreach ($records as $record) {
            $this->assertEditLockAvailable($type, (int) $record->id, (int) $request->user()->id);
        }

        DB::transaction(function () use ($records, $data, $type, $request): void {
            foreach ($records as $record) {
                $context = [
                    'type'    => $type,
                    'post_id' => $record->id,
                    'user_id' => $request->user()
                        ?->getAuthIdentifier(),
                ];

                $before = $this->historySnapshot($record);

                if ($data['operation'] === 'delete') {
                    Hooks::action('jobic.posts.deleting', $context);

                    $record->delete();

                    app(RecordHistory::class)->record('posts:' . $type, (int) $record->id, (int) $context['user_id'], 'delete', $before, []);
                    app(TaxonomyEditLocks::class)->forceRelease('posts:' . $type, (int) $record->id);

                    continue;
                }

                Hooks::action('jobic.posts.saving', [...$context, 'fields' => ['status']]);

                app(PostWorkflow::class)->transition($record, $data['operation'] === 'activate' ? 'publish' : 'disabled', $request->user());
                app(RecordHistory::class)->record('posts:' . $type, (int) $record->id, (int) $context['user_id'], 'update', $before, $this->historySnapshot($record));
            }
        });

        foreach ($records as $record) {
            $context = [
                'type'    => $type,
                'post_id' => $record->id,
                'user_id' => $request->user()
                    ?->getAuthIdentifier(),
            ];

            if ($data['operation'] === 'delete') {
                Hooks::notify('jobic.posts.deleted', $context);
            } else {
                Hooks::notify('jobic.posts.saved', [...$context, 'fields' => ['status']]);
            }
        }

        return response()
            ->json([
                'ok'      => true,
                'message' => trans('posts.bulk_done'),
                'data'    => [
                    'count' => $records->count(),
                ],
            ], 200)
            ->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Restore post-owned revision data with CMS authorization and audit hooks.
     *
     * @param Request $request
     * @param string $type
     * @param int $post
     * @param int $revision
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function restoreRevision(Request $request, string $type, int $post, int $revision): JsonResponse
    {
        $this->assertEditLockAvailable($type, $post, (int) $request->user()->id);

        if (PostTypeRegistry::for($type)
            ->hasOptimisticLocking()) {
            abort_unless(app(TaxonomyEditLocks::class)->canSave('posts:' . $type, $post, (int) $request->user()->id, (string) $request->input('_edit_lock', '')), 423, trans('posts.edit_lock_lost'));
        }

        $record = Post::query()
            ->where('type', $type)
            ->findOrFail($post);

        $record->revisions()
            ->findOrFail($revision);

        Hooks::action('jobic.posts.revision.restoring', [
            'type'        => $type,
            'post_id'     => $record->id,
            'revision_id' => $revision,
            'user_id'     => $request->user()
                ?->getAuthIdentifier(),
        ]);

        DB::transaction(function () use ($record, $revision, $type, $request): void {
            $before = $this->historySnapshot($record);
            $record->restoreRevision($revision);
            app(RecordHistory::class)->record('posts:' . $type, (int) $record->id, (int) $request->user()
                ->getAuthIdentifier(), 'restore', $before, $this->historySnapshot($record->fresh()));
        });

        Hooks::notify('jobic.posts.revision.restored', [
            'type'        => $type,
            'post_id'     => $record->id,
            'revision_id' => $revision,
            'user_id'     => $request->user()
                ?->getAuthIdentifier(),
        ]);

        return response()->json(['ok' => true, 'message' => trans('posts.revision_restored')]);
    }

    /**
     * Abort if the current user does not own the post's edit lock.
     *
     * @param string $type
     * @param int $post
     * @param int $userId
     *
     * @return void
     */
    private function assertEditLockAvailable(string $type, int $post, int $userId): void
    {
        if (! PostTypeRegistry::for($type)
            ->hasOptimisticLocking()) {
            return;
        }

        $lock = app(TaxonomyEditLocks::class)->inspect('posts:' . $type, $post);

        abort_if($lock && (int) ($lock['user_id'] ?? 0) !== $userId, 423, trans('posts.edit_locked', [
            'user' => $lock['user_name'] ?? trans('taxonomies.unknown_editor'),
            'time' => LocalizedDate::format($lock['locked_at'] ?? null),
        ]));
    }
}
