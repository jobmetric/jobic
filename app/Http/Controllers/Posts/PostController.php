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
use App\Services\Editor\EditorDocuments;
use App\Services\History\RecordHistory;
use App\Services\Posts\PostLifecycleView;
use App\Services\Posts\PostStateAccess;
use App\Services\Posts\PostPublicationProjection;
use App\Services\Posts\Posts;
use App\Services\Posts\PostWorkflow;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\AdminAccess;
use App\Support\EditorDriverRegistry;
use App\Support\EditorRenderer;
use App\Support\LocaleCatalog;
use App\Support\LocalizedDate;
use App\Support\Page;
use App\Support\PostWorkflowPermissions;
use App\Support\RecordVersion;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
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
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use JobMetric\Post\Support\PostTypeBuilder;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;
use JobMetric\Translation\Models\Translation;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/** Serve registry-driven post management screens and authorized draft operations. */
final class PostController extends Controller
{
    /**
     * Render a validated editor draft without persisting the post.
     *
     * @param Request $request
     * @param string $type
     *
     * @return JsonResponse
     * @throws Throwable
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function editorPreview(Request $request, string $type): JsonResponse
    {
        $data = $request->validate([
            'content' => [
                'required',
                new EditorContentRule(app(EditorDriverRegistry::class)),
            ],
        ]);

        return response()
            ->json(['html' => app(EditorRenderer::class)->render(app(EditorDocuments::class)->normalize($data['content']), 0, true)])
            ->header('Cache-Control', 'no-store, private');
    }

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
        $workflowPermissions = app(PostWorkflowPermissions::class)->for($type);

        $this->preparePage('list');

        $postType = new Post;
        $postType->type = $type;
        $activeDefinition = app(PostLifecycleView::class)->activeDefinition($type);
        $locale = LocaleCatalog::resolve(app()->getLocale());
        $statusLabels = $activeDefinition ? collect([
            'editable',
            'publish',
            'future',
            'archive',
            'disabled',
        ])
            ->mapWithKeys(static fn (string $status): array => [$status => __('posts.statuses.' . $status)])
            ->all() : (app(PostWorkflow::class)->allStatusOptions($postType, $locale) ?: trans('posts.statuses'));

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

        if ($builder->hasChangeStatusInList() && ! $activeDefinition) {
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
            'workflowUrl'        => (app(AdminAccess::class)->allows(auth()->user(), $workflowPermissions['manage']) || app(AdminAccess::class)->allows(auth()->user(), $workflowPermissions['view'])) ? route('admin.posts.workflow.index', ['type' => $type]) : null,
            'workflowLabel'      => trans('workflow.title'),
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
            'translationFields'  => $builder->getTranslationFields(),
            'hasSeo'             => $builder->isSeoEnabled(),
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
                'workbench' => trans('editor'),
                'tools'     => Hooks::filter('jobic.editor.tools', Editor::clientTools(), [
                    'type'    => $type,
                    'post_id' => null,
                ]),
                'uploadUrl' => route('admin.media-manager.upload'),
                'messages'  => trans('posts.editor'),
            ],
        ]);

        Page::addLocalize('postEditor', [
            'previewUrl' => route('admin.posts.editor-preview', ['type' => $type]),
            'templates'  => Editor::templates()
                ->forType($type),
            'workbench'  => trans('editor'),
            'tools'      => Hooks::filter('jobic.editor.tools', Editor::clientTools(), [
                'type'    => $type,
                'post_id' => null,
            ]),
            'messages'   => trans('posts.editor'),
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
        $activeDefinition = app(PostLifecycleView::class)->activeDefinition($type);

        $query = Post::query()
            ->where('type', $type)
            ->with([
                'translations',
                'files',
            ]);
        app(PostStateAccess::class)->scopeVisible($query, $type, $request->user());

        $filters = (array) $request->input('filters', []);
        $requestedStatus = $filters['status'] ?? $request->input('status');
        if ($activeDefinition && $requestedStatus !== 'archive') {
            $query->where('status', '<>', 'archive');
        }
        $total = (clone $query)->count();

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
                if ($activeDefinition) {
                    $query->orderBy(PostPublicationStream::query()
                        ->select('status')
                        ->whereColumn('post_id', (new Post)->qualifyColumn('id'))
                        ->where('locale', $locale)
                        ->limit(1), $direction);
                } else {
                    $query->orderBy('status', $direction);
                }
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
            ->with([
                'translations',
                'files',
            ])
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
                'fields'  => array_intersect_key(array_replace($record->getTranslations($locale), ['content' => app(EditorDocuments::class)->readForEditor($record, $locale)]), array_flip($fields)),
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

        $actor = $request->user();
        $changed = DB::transaction(function () use ($type, $post, $locale, &$fields, $context, $data, $actor): bool {
            app(PostWorkflowDefinitions::class)->lock($type);
            $record = Post::query()
                ->where('type', $type)
                ->lockForUpdate()
                ->findOrFail($post);

            if (app(PostLifecycleView::class)->activeDefinition($type)) {
                if ($record->status !== 'editable') {
                    throw ValidationException::withMessages(['status' => [trans('post::lifecycle.not_editable')]]);
                }
                if (\JobMetric\Post\Models\PostLocaleLock::query()
                    ->where('post_id', $record->id)
                    ->where('locale', $locale)
                    ->where('locked', true)
                    ->exists()) {
                    throw ValidationException::withMessages(['fields' => [trans('post::lifecycle.language_locked')]]);
                }
            }

            if (PostTypeRegistry::for($type)
                ->hasOptimisticLocking()) {
                abort_unless(hash_equals(RecordVersion::token($record, 'posts:' . $type), (string) $data['_version']), 409, trans('posts.conflict'));
            }

            if (array_key_exists('content', $fields) && $fields['content'] !== null && $fields['content'] !== '') {
                $document = is_string($fields['content']) ? json_decode($fields['content'], true) : $fields['content'];
                $document = Hooks::filter('jobic.editor.content', $document, [
                    'type'    => $type,
                    'locale'  => $locale,
                    'post_id' => $post,
                ]);
                $validator = Validator::make([
                    'content' => $document,
                ], [
                    'content' => [
                        'required',
                        new EditorContentRule(app(EditorDriverRegistry::class), app(EditorDocuments::class)->read($record, $locale)),
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

            $documents = app(EditorDocuments::class);
            $changed = array_filter($fields, static fn (
                $value,
                $field
            ): bool => ($field === 'content' ? $documents->read($record, $locale) : $record->getTranslation((string) $field, $locale)) !== $value, ARRAY_FILTER_USE_BOTH);

            if ($changed === []) {
                return false;
            }

            Hooks::lifecycle('jobic.posts.saving', $context);

            $historyBefore = $this->historySnapshot($record);
            $before = $record->snapshot();
            $documentsBefore = $documents->snapshot($record);
            if (array_key_exists('content', $changed)) {
                $documents->save($record, $locale, $changed['content']);
                unset($changed['content']);
            }
            $record->translate($locale, $changed);
            $revision = $record->revisions()
                ->create(['snapshot' => $before]);
            $documents->capture($revision, $documentsBefore);

            Post::withoutEvents(static fn () => $record->touch());

            app(PostPublicationProjection::class)->capture($record, [$locale], $actor);

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
            'previewUrl'   => route('admin.posts.editor-preview', ['type' => $type]),
            'templates'    => Editor::templates()
                ->forType($type),
            'workbench'    => trans('editor'),
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
                'create'           => trans($type === 'page' ? 'posts.create_page' : 'posts.create'),
                'edit'             => trans($type === 'page' ? 'posts.edit_page' : 'posts.edit'),
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
            'hasPassword'        => $builder->isPasswordEnabled(),
            'hasSeo'             => $builder->isSeoEnabled(),
            'activeLocale'       => $activeLocale,
            'record'             => $post,
            'lifecycle'          => $post ? app(PostLifecycleView::class)->for($post, $activeLocale) : null,
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
            Page::addPlugin(in_array(LocaleCatalog::calendar(), [
                'jalali',
                'persian',
            ], true) ? 'datepicker.jalali' : 'datepicker');
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
                    $definition = app(PostLifecycleView::class)->activeDefinition($type);
                    if ($definition) {
                        $statusLabels = collect([
                            'editable',
                            'publish',
                            'future',
                            'archive',
                            'disabled',
                        ])
                            ->mapWithKeys(static fn (string $status): array => [$status => __('posts.statuses.' . $status)])
                            ->all();
                    } else {
                        $postType = new Post;
                        $postType->type = $type;
                        $statusLabels = app(PostWorkflow::class)->allStatusOptions($postType, $locale) ?: trans('posts.statuses');
                    }
                }
                $filters[$field] = [
                    'kind'    => 'status',
                    'label'   => 'posts.status',
                    'options' => $statusLabels,
                ];

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

        $locale = LocaleCatalog::resolve((string) $request->query('locale', app()->getLocale()));
        $lifecycle = app(PostLifecycleView::class)->for($post, $locale);
        $workflowView = [
            'type'         => $type,
            'record'       => $post,
            'activeLocale' => $locale,
            'locales'      => LocaleCatalog::all(),
            'lifecycle'    => $lifecycle,
        ];
        $canViewDiagram = $lifecycle && app(AdminAccess::class)->allowsAny($request->user(), array_intersect_key(app(PostWorkflowPermissions::class)->for($type), array_flip([
                'view',
                'manage',
            ])));

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
                    'workflow_card_html'    => $lifecycle ? view('posts.partials.lifecycle-sidebar', $workflowView)->render() : null,
                    'workflow_preview_html' => $canViewDiagram ? view('posts.partials.lifecycle-preview', $workflowView)->render() : null,
                    'editable'              => ! $lifecycle || $post->status === 'editable',
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

        DB::transaction(function () use ($record, $type, $request): void {
            Hooks::lifecycle('jobic.posts.deleting', [
                'type'    => $type,
                'post_id' => $record->id,
                'user_id' => $request->user()
                    ?->getAuthIdentifier(),
            ]);
            app(PostWorkflowDefinitions::class)->lock($type);
            $record = Post::query()
                ->where('type', $type)
                ->lockForUpdate()
                ->findOrFail($record->id);
            $before = $this->historySnapshot($record);
            app(PostPublicationProjection::class)->withdrawAll($record, $request->user());
            app(\JobMetric\Post\Post::class)->delete($type, (int) $record->id);

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

        return response()->json([
            'ok'      => true,
            'message' => trans('posts.deleted'),
        ]);
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
            abort_unless(app(PostStateAccess::class)->scopeVisible(Post::query()->where('type', $type)->whereIn('id', $data['ids']), $type, $request->user())->count() === count($data['ids']), 403);

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

        abort_if(in_array($data['operation'], [
                'activate',
                'deactivate',
            ], true) && (! PostTypeRegistry::for($type)
                    ->hasChangeStatusInList() || app(PostLifecycleView::class)->activeDefinition($type) !== null), 403);

        $records = Post::query()
            ->where('type', $type)
            ->whereIn('id', $data['ids'])
            ->get();

        if ($records->count() !== count($data['ids'])) {
            throw ValidationException::withMessages([
                'ids' => [trans('posts.selection_invalid')],
            ]);
        }

        abort_unless($records->every(fn (Post $record): bool => app(PostStateAccess::class)->allows($record, $request->user())), 403);

        foreach ($records as $record) {
            $this->assertEditLockAvailable($type, (int) $record->id, (int) $request->user()->id);
        }

        DB::transaction(function () use ($records, $data, $type, $request): void {
            app(PostWorkflowDefinitions::class)->lock($type);
            foreach ($records as $record) {
                $record = Post::query()
                    ->where('type', $type)
                    ->lockForUpdate()
                    ->findOrFail($record->id);
                $context = [
                    'type'    => $type,
                    'post_id' => $record->id,
                    'user_id' => $request->user()
                        ?->getAuthIdentifier(),
                ];

                $before = $this->historySnapshot($record);

                if ($data['operation'] === 'delete') {
                    Hooks::lifecycle('jobic.posts.deleting', $context);

                    app(PostPublicationProjection::class)->withdrawAll($record, $request->user());
                    app(\JobMetric\Post\Post::class)->delete($type, (int) $record->id);

                    app(RecordHistory::class)->record('posts:' . $type, (int) $record->id, (int) $context['user_id'], 'delete', $before, []);
                    app(TaxonomyEditLocks::class)->forceRelease('posts:' . $type, (int) $record->id);

                    continue;
                }

                Hooks::lifecycle('jobic.posts.saving', [
                    ...$context,
                    'fields' => ['status'],
                ]);

                app(PostWorkflow::class)->transition($record, $data['operation'] === 'activate' ? 'publish' : 'disabled', $request->user());
                $locales = collect($record->snapshot()['translation'] ?? [])
                    ->pluck('locale')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                app(PostPublicationProjection::class)->capture($record->fresh(), $locales, $request->user());
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
                Hooks::notify('jobic.posts.saved', [
                    ...$context,
                    'fields' => ['status'],
                ]);
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

        DB::transaction(function () use ($record, $revision, $type, $request): void {
            Hooks::lifecycle('jobic.posts.revision.restoring', [
                'type'        => $type,
                'post_id'     => $record->id,
                'revision_id' => $revision,
                'user_id'     => $request->user()
                    ?->getAuthIdentifier(),
            ]);
            app(PostWorkflowDefinitions::class)->lock($type);
            $record = Post::query()
                ->where('type', $type)
                ->lockForUpdate()
                ->findOrFail($record->id);
            if (app(PostLifecycleView::class)->activeDefinition($type) && $record->status !== 'editable') {
                abort(409, trans('posts.workflow_read_only'));
            }
            if (app(PostLifecycleView::class)->activeDefinition($type) && \JobMetric\Post\Models\PostLocaleLock::query()
                    ->where('post_id', $record->id)
                    ->where('locked', true)
                    ->exists()) {
                abort(409, trans('post::lifecycle.language_locked'));
            }
            $before = $this->historySnapshot($record);
            $documents = app(EditorDocuments::class);
            $documentsBefore = $documents->snapshot($record);
            $lastRevision = (int) $record->revisions()
                ->max('id');
            $targetRevision = $record->revisions()
                ->findOrFail($revision);
            $record->restoreRevision($revision);
            $documents->restore($record, $targetRevision);
            $undo = $record->revisions()
                ->where('id', '>', $lastRevision)
                ->latest('id')
                ->first();
            if ($undo) {
                $documents->capture($undo, $documentsBefore);
            }
            $locales = collect($record->snapshot()['translation'] ?? [])
                ->pluck('locale')
                ->filter()
                ->unique()
                ->values()
                ->all();
            app(PostPublicationProjection::class)->capture($record->fresh(), $locales, $request->user(), null, false);
            app(PostLifecycle::class)->publishRestoration($record->fresh(), $request->user());
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

        return response()->json([
            'ok'      => true,
            'message' => trans('posts.revision_restored'),
        ]);
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
