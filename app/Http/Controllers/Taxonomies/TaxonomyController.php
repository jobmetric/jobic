<?php

declare(strict_types=1);

namespace App\Http\Controllers\Taxonomies;

use App\Facades\Hooks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Taxonomies\BulkRequest;
use App\Http\Requests\Taxonomies\HistoryRequest;
use App\Http\Requests\Taxonomies\TableRequest;
use App\Http\Requests\Taxonomies\TaxonomyRequest;
use App\Http\Requests\Taxonomies\UnlockEditLocksRequest;
use App\Services\Taxonomies\Taxonomies;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\AdminAccess;
use App\Support\LocaleCatalog;
use App\Support\LocalizedDate;
use App\Support\Page;
use App\Support\RecordVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy as TaxonomyModel;
use Throwable;

/** Render the native, registry-driven taxonomy administration pages. */
class TaxonomyController extends Controller
{
    public function __construct(
        private readonly Taxonomies $taxonomies,
        private readonly TaxonomyEditLocks $editLocks,
        private readonly AdminAccess $access,
    ) {
    }

    /**
     * Render the index page for a taxonomy type.
     *
     * @param string $type
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function index(string $type): View
    {
        $this->prepare($type);
        Domi::setPlugins(['jquery', 'datatable']);

        return view('taxonomies.index', [
            'type'        => $type,
            'listFilters' => $this->taxonomies->listFilters($type),
        ]);
    }

    /**
     * Return one bounded page of taxonomy records for a type.
     *
     * @param TableRequest $request
     * @param string $type
     *
     * @return JsonResponse
     */
    public function data(TableRequest $request, string $type): JsonResponse
    {
        return response()->json($this->taxonomies->table($type, $request->validated()));
    }

    /**
     * Render the form page for creating a new taxonomy record.
     *
     * @param string $type
     *
     * @return View
     * @throws SetPluginNotFoundException
     * @throws Throwable
     */
    public function create(string $type): View
    {
        $this->prepare($type);

        return view('taxonomies.form', $this->formData($type));
    }

    /**
     * Render fields contributed by a driver registered for this taxonomy type.
     *
     * @param string $type
     * @param string $driver
     *
     * @return HttpResponse
     */
    public function driverFields(string $type, string $driver): HttpResponse
    {
        $html = Hooks::filter('jobic.taxonomies.driver.fields', '', [
            'type'   => $type,
            'driver' => $driver,
        ]);

        abort_unless(is_string($html) && $html !== '', 404);

        return response($html);
    }

    /**
     * Render the form page for editing an existing taxonomy record.
     *
     * @param string $type
     * @param int $taxonomy
     *
     * @return View|RedirectResponse
     * @throws SetPluginNotFoundException
     * @throws Throwable
     */
    public function edit(string $type, int $taxonomy): View|RedirectResponse
    {
        $record = $this->taxonomies->record($type, $taxonomy);
        $editLock = null;

        if (TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false)) {
            $result = $this->editLocks->acquire($type, $taxonomy, request()->user());

            if (! $result['acquired']) {
                $lock = $result['lock'] ?? [];

                return redirect()
                    ->route('admin.taxonomies.index', $type)
                    ->with('error', __('taxonomies.edit_locked', [
                        'user' => $lock['user_name'] ?? __('taxonomies.unknown_editor'),
                        'time' => LocalizedDate::format($lock['locked_at'] ?? null),
                    ]));
            }

            $editLock = $result['lock'];
        }

        $this->prepare($type);

        return view('taxonomies.form', $this->formData($type, $record, $editLock));
    }

    /**
     * Renew the edit lock for a taxonomy record if the user is still actively editing it.
     *
     * @param Request $request
     * @param string $type
     * @param int $taxonomy
     *
     * @return JsonResponse
     */
    public function renewEditLock(Request $request, string $type, int $taxonomy): JsonResponse
    {
        $token = (string) $request->input('_edit_lock', '');
        $refreshed = $token !== '' && $this->editLocks->refresh($type, $taxonomy, (int) $request->user()->id, $token);

        return response()->json([
            'ok'      => $refreshed,
            'message' => $refreshed ? __('taxonomies.edit_lock_refreshed') : __('taxonomies.edit_lock_lost'),
        ], $refreshed ? 200 : 423);
    }

    /**
     * Release the edit lock for a taxonomy record if the user is done editing it.
     *
     * @param Request $request
     * @param string $type
     * @param int $taxonomy
     *
     * @return JsonResponse
     */
    public function releaseEditLock(Request $request, string $type, int $taxonomy): JsonResponse
    {
        $token = (string) $request->input('_edit_lock', '');
        $released = $token !== '' && $this->editLocks->release($type, $taxonomy, (int) $request->user()->id, $token);

        return response()->json([
            'ok'      => $released,
            'message' => $released ? __('taxonomies.edit_lock_released') : __('taxonomies.edit_lock_lost'),
        ], $released ? 200 : 423);
    }

    /**
     * Forcefully release the edit lock for a taxonomy record, regardless of who holds it.
     *
     * @param string $type
     * @param int $taxonomy
     *
     * @return JsonResponse
     */
    public function unlockEditLock(string $type, int $taxonomy): JsonResponse
    {
        $this->taxonomies->record($type, $taxonomy);
        $lock = $this->editLocks->forceRelease($type, $taxonomy);

        return response()->json([
            'ok'      => true,
            'message' => $lock ? __('taxonomies.edit_lock_released') : __('taxonomies.edit_lock_already_released'),
        ]);
    }

    /**
     * Forcefully release the edit locks for multiple taxonomy records, regardless of who holds them.
     *
     * @param UnlockEditLocksRequest $request
     * @param string $type
     *
     * @return JsonResponse
     */
    public function unlockEditLocks(UnlockEditLocksRequest $request, string $type): JsonResponse
    {
        $ids = array_map('intval', $request->validated('ids'));
        $existingIds = TaxonomyModel::query()
            ->where('type', $type)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        abort_unless(count($existingIds) === count(array_unique($ids)), 404);

        $released = 0;
        foreach ($existingIds as $id) {
            if ($this->editLocks->inspect($type, $id) !== null) {
                $this->editLocks->forceRelease($type, $id);
                $released++;
            }
        }

        return response()->json([
            'ok'      => true,
            'message' => trans('taxonomies.edit_locks_released', [
                'count' => $released,
            ]),
            'data'    => [
                'released' => $released,
            ],
        ]);
    }

    /**
     * Store a new taxonomy record for a type.
     *
     * @param TaxonomyRequest $request
     * @param string $type
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(TaxonomyRequest $request, string $type): JsonResponse
    {
        $result = $this->taxonomies->save($type, $request->validated());
        if ($result->ok) {
            $result->data['edit_url'] = route('admin.taxonomies.edit', [$type, $result->data['id']]);
        }

        return response()->json($result, $result->status);
    }

    /**
     * Update an existing taxonomy record for a type.
     *
     * @param TaxonomyRequest $request
     * @param string $type
     * @param int $taxonomy
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(TaxonomyRequest $request, string $type, int $taxonomy): JsonResponse
    {
        $result = $this->taxonomies->save($type, $request->validated(), $taxonomy);
        if ($result->ok) {
            $result->data['edit_url'] = route('admin.taxonomies.edit', [$type, $taxonomy]);
        }

        return response()->json($result, $result->status);
    }

    /**
     * Save a translation for a taxonomy record in a specific locale.
     *
     * @param Request $request
     * @param string $type
     * @param int $taxonomy
     * @param string $locale
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function translation(Request $request, string $type, int $taxonomy, string $locale): JsonResponse
    {
        abort_unless(array_key_exists($locale, LocaleCatalog::all()), 404);

        $record = $this->taxonomies->record($type, $taxonomy);
        $builder = TaxonomyTypeRegistry::for($type);

        $rules = [];
        foreach ($builder->getTranslation() as $item) {
            $field = (string) Arr::get($item->customField->params, 'uniqName');

            if ($field !== '') {
                $maxLength = match ($field) {
                    'name', 'meta_title' => 255,
                    'meta_description' => 500,
                    'meta_keywords' => 1000,
                    default => null,
                };

                $rules[$field] = [
                    $field === 'name' ? 'required' : 'nullable',
                    'string',
                    ...($maxLength === null ? [] : ['max:' . $maxLength]),
                ];
            }
        }

        $values = Validator::make((array) $request->input('fields'), $rules)
            ->validate();

        $result = $this->taxonomies->saveTranslation($type, $record, $locale, $values);

        return response()->json($result, $result->status);
    }

    /**
     * Delete an existing taxonomy record for a type.
     *
     * @param string $type
     * @param int $taxonomy
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function destroy(string $type, int $taxonomy): JsonResponse
    {
        $result = $this->taxonomies->delete($type, $taxonomy);

        return response()->json($result, $result->status);
    }

    /**
     * Perform bulk actions on taxonomy records for a type.
     *
     * @param BulkRequest $request
     * @param string $type
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function bulk(BulkRequest $request, string $type): JsonResponse
    {
        $result = $this->taxonomies->bulk($type, $request->validated());

        return response()->json($result, $result->status);
    }

    /**
     * Return the history of changes for a taxonomy record.
     *
     * @param HistoryRequest $request
     * @param string $type
     * @param int $taxonomy
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function history(HistoryRequest $request, string $type, int $taxonomy): JsonResponse
    {
        return response()->json($this->taxonomies->history($type, $taxonomy, $request->validated()));
    }

    /**
     * Prepare the page context for a taxonomy type, including localization and configuration.
     *
     * @param string $type
     *
     * @return void
     * @throws SetPluginNotFoundException
     */
    private function prepare(string $type): void
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);
        Hooks::action('jobic.taxonomies.preparing', [
            'type' => $type,
        ]);

        $builder = TaxonomyTypeRegistry::for($type);
        Page::prepare('taxonomies');

        $context = [
            'type' => $type,
        ];

        Page::addLocalize('taxonomies', trans('taxonomies'));
        Page::addLocalize('fileManager', trans('file-manager'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));

        $translationFields = $builder->getTranslation()
            ->map(function ($item): array {
                $field = (string) Arr::get($item->customField->params, 'uniqName');
                $translated = trans((string) ($item->customField->label ?: $field));

                if ($translated === $item->customField->label) {
                    $translated = trans('taxonomies.' . $field);
                }

                return [
                    'name'  => $field,
                    'label' => $translated,
                    'type'  => $field !== 'name' && in_array($item->customField->type, [
                        'textarea',
                        'editor',
                    ], true) ? 'textarea' : 'text',
                ];
            })
            ->filter(fn (array $field): bool => $field['name'] !== '')
            ->values()
            ->all();

        $columns = [
            'name',
            'ordering',
            'status',
            'translations',
        ];

        if ($builder->hasBaseMedia() || $builder->getMedia()
                ->isNotEmpty()) {
            array_unshift($columns, 'media');
        }

        $rowActions = Hooks::filter('jobic.taxonomies.list.actions', array_merge([
            'edit',
            'delete',
        ], (array) TaxonomyTypeRegistry::getOption($type, 'row-actions', [])), $context);

        $toolbar = [
            'activate',
            'deactivate',
            'delete',
        ];

        if ($builder->hasHelp()) {
            $toolbar[] = 'help';
        }

        $unlockPermission = (string) ($builder->getUnlockPermission() ?? config('admin.permissions.unlock_edit_lock', 'admin.unlock_edit_lock'));
        $canUnlockEditLocks = $this->access->allows(auth()->user(), $unlockPermission);

        if ($builder->hasOptimisticLocking() && $canUnlockEditLocks) {
            $toolbar[] = 'unlock';
        }

        Page::addLocalize('taxonomyPage', [
            'index'              => route('admin.taxonomies.index', $type),
            'data'               => route('admin.taxonomies.data', $type),
            'bulk'               => route('admin.taxonomies.bulk', $type),
            'type'               => $type,
            'label'              => $builder->getLabel(),
            'columns'            => Hooks::filter('jobic.taxonomies.list.columns', $columns, $context),
            'languages'          => collect(LocaleCatalog::all())
                ->map(fn (array $language): array => $language + [
                        'flag_url' => LocaleCatalog::flagUrl($language['flag'] ?? null),
                    ])
                ->all(),
            'activeLocale'       => LocaleCatalog::resolve(app()->getLocale()),
            'hierarchical'       => $builder->hasHierarchical(),
            'backUrl'            => TaxonomyTypeRegistry::getOption($type, 'back-url'),
            'canUnlockEditLocks' => $canUnlockEditLocks,
            'editLockUnlockUrl'  => route('admin.taxonomies.edit-lock.unlock', [$type, '__ID__']),
            'editLocksUnlockUrl' => route('admin.taxonomies.edit-locks.unlock', $type),
            'translationFields'  => $translationFields,
            'translationUrl'     => route('admin.taxonomies.translation', [
                $type,
                '__ID__',
                '__LOCALE__',
            ]),
            'rowActions'         => $rowActions,
            'toolbar'            => Hooks::filter('jobic.taxonomies.list.toolbar', $toolbar, $context),
            'formToolbar'        => Hooks::filter('jobic.taxonomies.form.toolbar', [
                'new',
                'close',
            ], $context),
            'help'               => Hooks::filter('jobic.taxonomies.help.items', trans('taxonomies.help_items'), $context),
            'helpIntro'          => Hooks::filter('jobic.taxonomies.help.intro', trans('taxonomies.help_intro'), $context),
        ]);
    }

    /**
     * Prepare the data for rendering the taxonomy form page, including record details, edit lock information, and media manager configuration.
     *
     * @param string $type
     * @param mixed|null $record
     * @param array|null $editLock
     *
     * @return array
     * @throws Throwable
     */
    private function formData(string $type, mixed $record = null, ?array $editLock = null): array
    {
        $builder = TaxonomyTypeRegistry::for($type);

        return [
            'type'               => $type,
            'record'             => $record,
            'version'            => $record && TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false) ? RecordVersion::token($record, 'taxonomies:' . $type) : null,
            'editLock'           => $editLock,
            'editLockRenewUrl'   => $record ? route('admin.taxonomies.edit-lock.renew', [$type, $record->id]) : null,
            'editLockReleaseUrl' => $record ? route('admin.taxonomies.edit-lock.release', [$type, $record->id]) : null,
            'builder'            => $builder,
            'capabilityValues'   => $this->taxonomies->capabilityValues($record),
            'parents'            => $builder->hasHierarchical() ? $this->taxonomies->parentOptions($type, $record?->id) : [],
            'languages'          => collect(LocaleCatalog::all())
                ->map(fn (array $language): array => $language + [
                        'flag_url' => LocaleCatalog::flagUrl($language['flag'] ?? null),
                    ])
                ->all(),
            'activeLocale'       => LocaleCatalog::resolve(app()->getLocale()),
            'translations'       => $record?->getTranslations() ?? [],
            'mediaSelections'    => $record && ($builder->hasBaseMedia() || $builder->getMedia()
                    ->isNotEmpty()) ? $this->taxonomies->mediaSelections($record) : [],
            'mediaManager'       => [
                'browse'  => route('admin.media-manager.browse'),
                'upload'  => route('admin.media-manager.upload'),
                'folder'  => route('admin.media-manager.folders.store'),
                'details' => route('admin.media-manager.details', '__ID__'),
                'rename'  => route('admin.media-manager.rename', '__ID__'),
                'destroy' => route('admin.media-manager.destroy'),
            ],
        ];
    }
}
