<?php

declare(strict_types=1);

namespace App\Services\Taxonomies;

use App\Facades\Hooks;
use App\Http\Requests\Taxonomies\HistoryRequest;
use App\Services\History\RecordHistory;
use App\Support\LocaleCatalog;
use App\Support\LocalizedDate;
use App\Support\RecordVersion;
use App\Support\TaxonomyPresentation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JobMetric\Media\Enums\MediaTypeEnum;
use JobMetric\Media\Models\Media;
use JobMetric\Metadata\Models\Meta;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Taxonomy\Facades\Taxonomy;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Http\Requests\StoreTaxonomyRequest;
use JobMetric\Taxonomy\Http\Requests\UpdateTaxonomyRequest;
use JobMetric\Taxonomy\Models\Taxonomy as TaxonomyModel;
use JobMetric\Translation\Models\Translation;
use Throwable;

/** Provide one shared application service for every registered taxonomy type. */
class Taxonomies
{
    /**
     * Build a bounded taxonomy table with sibling-local hierarchical name ordering.
     *
     * @param string $type
     * @param array<string, mixed> $input
     *
     * @return Response
     */
    public function table(string $type, array $input): Response
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);

        $builder = TaxonomyTypeRegistry::for($type);
        $query = TaxonomyModel::query()
            ->where('type', $type);

        $total = (clone $query)->count();

        $filters = (array) ($input['filters'] ?? []);
        foreach ($this->listFilters($type) as $key => $filter) {
            $value = $filters[$key] ?? ($key === 'status' ? ($input['status'] ?? null) : null);
            if (! is_scalar($value) || (string) $value === '') {
                continue;
            }
            if ($filter['kind'] === 'status' && array_key_exists((string) $value, $filter['options'])) {
                $query->where('status', (string) $value);
            } else {
                if ($filter['kind'] === 'taxonomy' && array_key_exists((string) $value, $filter['options'])) {
                    $query->whereHas('taxonomies', fn ($taxonomy) => $taxonomy->whereKey((int) $value)
                        ->where('type', $filter['type'])
                        ->where(config('taxonomy.tables.taxonomy_relation', 'taxonomy_relations') . '.collection', $filter['collection']));
                } else {
                    if ($filter['kind'] === 'metadata' && array_key_exists((string) $value, $filter['options'])) {
                        $query->whereHas('metas', fn ($meta) => $meta->where('key', $filter['field'])
                            ->where('value', (string) $value));
                    } else {
                        if ($filter['kind'] === 'custom' && array_key_exists((string) $value, $filter['options'])) {
                            ($filter['apply'])($query, (string) $value, $input);
                        }
                    }
                }
            }
        }

        if ($term = ($input['search']['value'] ?? null)) {
            $query->whereHas('translations', fn ($translation) => $translation->where('field', 'name')
                ->where('value', 'like', '%' . $term . '%'));
        }

        $filtered = (clone $query)->count();
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
        $requestedColumn = $columns[max(0, (int) ($input['order'][0]['column'] ?? 1) - 1)] ?? 'ordering';
        $orderColumn = in_array($requestedColumn, [
            'ordering',
            'status',
        ], true) ? $requestedColumn : null;

        $with = ['translations'];
        if ($builder->hasHierarchical()) {
            $with[] = 'paths.path.translations';
        }

        $query->with($with)
            ->offset((int) $input['start'])
            ->limit((int) $input['length']);

        if ($orderColumn) {
            $query->orderBy($orderColumn, $input['order'][0]['dir'] ?? 'asc');
        } else {
            $name = Translation::query()
                ->select('value')
                ->whereColumn('translatable_id', (new TaxonomyModel)->qualifyColumn('id'))
                ->where('translatable_type', TaxonomyModel::class)
                ->where('field', 'name')
                ->where('locale', LocaleCatalog::resolve(app()->getLocale()))
                ->latest('version')
                ->limit(1);
            if ($builder->hasHierarchical()) {
                $direction = ($input['order'][0]['dir'] ?? 'asc') === 'desc' ? 'DESC' : 'ASC';
                $model = new TaxonomyModel;

                $ranked = TaxonomyModel::query()
                    ->where('type', $type)
                    ->select('id', 'parent_id')
                    ->selectRaw('ROW_NUMBER() OVER (ORDER BY (' . $name->toSql() . ') ' . $direction . ', id) AS sibling_rank', $name->getBindings());

                $sqlite = DB::connection()
                        ->getDriverName() === 'sqlite';
                $segment = $sqlite ? "printf('%012d', sibling_rank)" : "LPAD(sibling_rank, 12, '0')";
                $childSegment = $sqlite ? "tree.tree_order || '.' || printf('%012d', child.sibling_rank)" : "CONCAT(tree.tree_order, '.', LPAD(child.sibling_rank, 12, '0'))";
                $seed = $sqlite ? $segment : 'CAST(' . $segment . ' AS CHAR(4096))';

                $sql = 'WITH RECURSIVE ranked AS (' . $ranked->toSql() . '), tree AS (' . 'SELECT id, ' . $seed . ' AS tree_order FROM ranked WHERE parent_id IS NULL ' . 'UNION ALL SELECT child.id, ' . $childSegment . ' FROM ranked child JOIN tree ON child.parent_id = tree.id' . ') SELECT id, tree_order FROM tree';

                $tree = DB::query()
                    ->fromRaw('(' . $sql . ') AS taxonomy_tree', $ranked->getBindings());

                $query->joinSub($tree, 'tree_ordering', 'tree_ordering.id', '=', $model->qualifyColumn('id'))
                    ->select($model->qualifyColumn('*'))
                    ->orderBy('tree_ordering.tree_order');
            } else {
                $query->orderBy($name, $input['order'][0]['dir'] ?? 'asc');
            }
        }

        $rows = $query->orderBy((new TaxonomyModel)->qualifyColumn('id'))
            ->get()
            ->map(fn (TaxonomyModel $record): array => Hooks::filter('jobic.taxonomies.list.row', [
                'id'           => $record->id,
                'name'         => TaxonomyPresentation::name($record),
                'path_names'   => $builder->hasHierarchical() ? $record->paths->sortBy('level')
                    ->map(fn ($path): string => TaxonomyPresentation::name($path->path))
                    ->values()
                    ->all() : [],
                'translations' => $record->getTranslations(),
                'edit_lock'    => $this->editLockDetails($type, (int) $record->id),
                'media'        => ($builder->hasBaseMedia() || $builder->getMedia()
                        ->isNotEmpty()) ? $this->mediaPreview($record) : null,
                'ordering'     => $record->ordering,
                'status'       => $record->status,
            ], [
                'type' => $type,
                'id'   => $record->id,
            ]))
            ->values();

        return Response::make(true, '', [
            'draw'            => (int) $input['draw'],
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $rows,
        ]);
    }

    /**
     * Build the configured field filters for a taxonomy list.
     *
     * @param string $type
     *
     * @return array<string, array<string, mixed>>
     */
    public function listFilters(string $type): array
    {
        $builder = TaxonomyTypeRegistry::for($type);
        $filters = [];
        foreach ($builder->getFilterFields() as $field) {
            if ($field === 'status') {
                $filters[$field] = [
                    'kind'    => 'status',
                    'label'   => 'taxonomies.status',
                    'options' => [
                        '1' => 'taxonomies.active',
                        '0' => 'taxonomies.inactive',
                    ],
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
                    'options'    => TaxonomyModel::query()
                        ->where('type', $taxonomyType)
                        ->with('translations')
                        ->get()
                        ->mapWithKeys(fn (TaxonomyModel $taxonomy): array => [(string) $taxonomy->id => TaxonomyPresentation::name($taxonomy)])
                        ->all(),
                ];
                continue;
            }
            if (str_starts_with($field, 'metadata.')) {
                $metadataKey = substr($field, strlen('metadata.'));
                $metadataDefinition = $builder->getMetadata()
                    ->first(fn ($metadata): bool => (string) data_get($metadata, 'customField.params.uniqName') === $metadataKey);
                $label = data_get($metadataDefinition, 'customField.label') ?: $metadataKey;
                $values = Meta::query()
                    ->where('metaable_type', (new TaxonomyModel)->getMorphClass())
                    ->where('key', $metadataKey)
                    ->whereIn('metaable_id', TaxonomyModel::query()
                        ->where('type', $type)
                        ->select('id'))
                    ->where('is_json', false)
                    ->whereNotNull('value')
                    ->distinct()
                    ->orderBy('value')
                    ->limit(100)
                    ->pluck('value')
                    ->mapWithKeys(fn ($value): array => [(string) $value => (string) $value])
                    ->all();
                $filters[$field] = [
                    'kind'    => 'metadata',
                    'field'   => $metadataKey,
                    'label'   => $label,
                    'options' => $values,
                ];
            }
        }
        foreach ($builder->getCustomFilters() as $key => $filter) {
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
     * Return the registered type definition for one taxonomy type.
     *
     * @param string $type
     *
     * @return array
     */
    public function type(string $type): array
    {
        return TaxonomyTypeRegistry::get($type);
    }

    /**
     * Return all records of one taxonomy type, ordered by hierarchy and ordering.
     *
     * @param string $type
     *
     * @return Collection
     */
    public function records(string $type): Collection
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);

        return TaxonomyModel::query()
            ->with('translations')
            ->where('type', $type)
            ->orderBy('ordering')
            ->orderBy('id')
            ->get();
    }

    /**
     * Return a flat list of parent options for one taxonomy type, excluding a specific record if provided.
     *
     * @param string $type
     * @param int|null $exceptId
     *
     * @return array
     */
    public function parentOptions(string $type, ?int $exceptId = null): array
    {
        $records = $this->records($type);
        $children = $records->groupBy(fn (TaxonomyModel $record): int => (int) ($record->parent_id ?? 0));

        $options = [];
        $walk = function (int $parentId, int $depth) use (&$walk, &$options, $children, $exceptId): void {
            foreach ($children->get($parentId, collect()) as $record) {
                if ((int) $record->id === $exceptId) {
                    continue;
                }

                $options[] = [
                    'id'    => (int) $record->id,
                    'name'  => TaxonomyPresentation::name($record),
                    'depth' => $depth,
                ];

                $walk((int) $record->id, $depth + 1);
            }
        };

        $walk(0, 0);

        return $options;
    }

    /**
     * Return one taxonomy record by type and ID, or fail with 404.
     *
     * @param string $type
     * @param int $id
     *
     * @return TaxonomyModel
     */
    public function record(string $type, int $id): TaxonomyModel
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);

        return TaxonomyModel::query()
            ->with('translations')
            ->where('type', $type)
            ->findOrFail($id);
    }

    /**
     * Create or update one taxonomy record with optional media and metadata.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param int|null $id
     *
     * @return Response
     * @throws Throwable
     */
    public function save(string $type, array $data, ?int $id = null): Response
    {
        $builder = TaxonomyTypeRegistry::for($type);

        if ($id !== null) {
            $this->record($type, $id);
        }

        $payload = [
            'type'        => $type,
            'ordering'    => $data['ordering'],
            'status'      => $data['status'],
            'translation' => array_filter($data['translation'], fn (array $fields): bool => collect($fields)->contains(fn ($value) => filled($value))),
        ];

        if ($builder->hasHierarchical()) {
            $payload['parent_id'] = $data['parent_id'] ?? null;
        }

        if ($builder->hasUrl() && filled($data['slug'] ?? null)) {
            $payload['slug'] = $data['slug'];
        }

        $mediaDefinitions = collect($builder->hasBaseMedia() ? [
            [
                'collection' => 'base',
                'multiple'   => false,
                'mime'       => ['image'],
            ],
        ] : [])
            ->concat($builder->getMedia()
                ->map(fn ($item): array => [
                    'collection' => $item->getCollection(),
                    'multiple'   => $item->getMultiple(),
                    'mime'       => $item->getMimeTypes(),
                ]))
            ->keyBy('collection');

        $mediaPayload = collect((array) ($data['media_selection'] ?? []))
            ->mapWithKeys(function ($value, string $collection) use ($mediaDefinitions): array {
                $definition = $mediaDefinitions->get($collection);

                if (! $definition) {
                    throw ValidationException::withMessages(["media_selection.$collection" => __('taxonomies.media_invalid')]);
                }

                $ids = collect(json_decode((string) $value, true) ?: [])
                    ->map(fn ($id): int => (int) $id)
                    ->filter()
                    ->unique()
                    ->values();

                if (! $definition['multiple']) {
                    $ids = $ids->take(1);
                }

                $valid = Media::query()
                    ->whereIn('id', $ids)
                    ->where('type', MediaTypeEnum::FILE())
                    ->get([
                        'id',
                        'mime_type',
                    ]);

                $validGroups = collect($definition['mime'])
                    ->filter()
                    ->values();

                if ($valid->count() !== $ids->count() || ($validGroups->isNotEmpty() && $valid->contains(fn (
                            Media $media
                        ): bool => ! $validGroups->contains(getMimeGroup((string) $media->mime_type))))) {
                    throw ValidationException::withMessages(["media_selection.$collection" => __('taxonomies.media_invalid')]);
                }

                return [
                    $collection => $valid->pluck('id')
                        ->all(),
                ];
            })
            ->all();

        $context = [
            'type'      => $type,
            'id'        => $id,
            'operation' => $id ? 'update' : 'create',
            'user_id'   => (int) auth()->id(),
        ];
        $savedId = DB::transaction(function () use ($type, $id, $payload, $mediaPayload, $context, $data): int|Response {
            $current = $id ? TaxonomyModel::query()
                ->where('type', $type)
                ->lockForUpdate()
                ->findOrFail($id) : null;

            if ($current && TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false)) {
                $token = (string) ($data['_edit_lock'] ?? '');
                if (! app(TaxonomyEditLocks::class)->canSave($type, (int) $current->id, (int) auth()->id(), $token)) {
                    $lock = app(TaxonomyEditLocks::class)->inspect($type, (int) $current->id) ?? [];
                    $message = $lock && (int) ($lock['user_id'] ?? 0) !== (int) auth()->id() ? __('taxonomies.edit_locked', [
                        'user' => $lock['user_name'] ?? __('taxonomies.unknown_editor'),
                        'time' => LocalizedDate::format($lock['locked_at'] ?? null),
                    ]) : __('taxonomies.edit_lock_lost');

                    return Response::make(false, $message, null, 423);
                }
            }

            if ($current && TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false) && ! hash_equals(RecordVersion::token($current, 'taxonomies:' . $type), (string) ($data['_version'] ?? ''))) {
                return Response::make(false, __('taxonomies.record_conflict'), null, 409);
            }

            $packageRequest = $id ? (new UpdateTaxonomyRequest)->setType($type)
                ->setTaxonomyId($id) : new StoreTaxonomyRequest;

            $validationPayload = $payload + ($id ? ['taxonomy_id' => $id] : []);

            Validator::make($validationPayload, $packageRequest->setData($validationPayload)
                ->rules())
                ->validate();

            $before = $current ? $this->historySnapshot($current) : [];

            Hooks::lifecycle('jobic.taxonomies.saving', $context);

            $result = $id ? Taxonomy::update($id, $payload) : Taxonomy::store($payload);

            if (! ($result['ok'] ?? false)) {
                throw ValidationException::withMessages($result['errors'] ?: ['taxonomy' => [$result['message']]]);
            }

            $savedId = (int) $result['data']->id;
            foreach ($mediaPayload as $collection => $mediaIds) {
                DB::table(config('media.tables.media_relation'))
                    ->where([
                        'mediaable_type' => TaxonomyModel::class,
                        'mediaable_id'   => $savedId,
                        'collection'     => $collection,
                    ])
                    ->delete();

                foreach ((array) $mediaIds as $mediaId) {
                    DB::table(config('media.tables.media_relation'))
                        ->insert([
                            'media_id'       => $mediaId,
                            'mediaable_type' => TaxonomyModel::class,
                            'mediaable_id'   => $savedId,
                            'collection'     => $collection,
                            'created_at'     => now(),
                        ]);
                }
            }

            $saved = $this->record($type, $savedId);

            $definition = TaxonomyTypeRegistry::for($type);

            if (($definition->get()['custom-fields'] ?? []) && array_key_exists('custom_fields', $data)) {
                $saved->metas()
                    ->updateOrCreate(['key' => 'taxonomy_fields'], [
                        'value'   => json_encode(array_intersect_key($data['custom_fields'], ($definition->get()['custom-fields'] ?? [])), JSON_UNESCAPED_UNICODE),
                        'is_json' => true,
                    ]);
            }

            if ($definition->isSeoEnabled() && array_key_exists('seo_index', (array) ($data['metadata'] ?? []))) {
                $saved->metas()
                    ->updateOrCreate(['key' => 'seo_index'], [
                        'value'   => (string) $data['metadata']['seo_index'],
                        'is_json' => false,
                    ]);
            }

            Hooks::lifecycle('jobic.taxonomies.persisted', [
                ...$context,
                'id'     => $savedId,
                'record' => $saved,
                'data'   => $data,
            ]);
            app(RecordHistory::class)->record('taxonomies:' . $type, $savedId, $context['user_id'], $context['operation'], $before, $this->historySnapshot($saved));

            return $savedId;
        });

        if ($savedId instanceof Response) {
            return $savedId;
        }

        $savedRecord = $this->record($type, $savedId);
        $version = TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false) ? RecordVersion::token($savedRecord, 'taxonomies:' . $type) : null;

        Hooks::notify('jobic.taxonomies.saved', array_replace($context, ['id' => $savedId]));

        return Response::make(true, __('taxonomies.saved'), [
            'id'      => $savedId,
            'version' => $version,
        ], $id ? 200 : 201);
    }

    /**
     * Delete one taxonomy record and its media relations, with history recording.
     *
     * @param string $type
     * @param int $id
     *
     * @return Response
     * @throws Throwable
     */
    public function delete(string $type, int $id): Response
    {
        $record = $this->record($type, $id);
        $before = $this->historySnapshot($record);

        $context = [
            'type'      => $type,
            'id'        => $id,
            'operation' => 'delete',
            'user_id'   => (int) auth()->id(),
        ];

        DB::transaction(function () use ($record, $type, $before, $context): void {
            Hooks::lifecycle('jobic.taxonomies.deleting', $context);
            Hooks::lifecycle('jobic.taxonomies.deleting-record', [
                ...$context,
                'record' => $record,
            ]);
            Taxonomy::delete($record->id);

            app(RecordHistory::class)->record('taxonomies:' . $type, $record->id, $context['user_id'], 'delete', $before, []);
        });

        Hooks::notify('jobic.taxonomies.deleted', $context);
        app(TaxonomyEditLocks::class)->forceRelease($type, $id);

        return Response::make(true, __('taxonomies.deleted'));
    }

    /**
     * Apply an all-or-nothing bulk mutation to explicitly selected records.
     *
     * @param string $type
     * @param array<string, mixed> $data
     *
     * @return Response
     * @throws Throwable
     */
    public function bulk(string $type, array $data): Response
    {
        foreach ($data['ids'] as $id) {
            if ($data['operation'] === 'delete') {
                $this->delete($type, (int) $id);
            } else {
                $record = $this->record($type, (int) $id);
                $input = [
                    'translation' => $record->getTranslations(),
                    'ordering'    => $record->ordering,
                    'status'      => $data['operation'] === 'activate',
                    'parent_id'   => $record->parent_id,
                    '_version'    => TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false) ? RecordVersion::token($record, 'taxonomies:' . $type) : null,
                ];
                $temporaryLock = false;
                if (TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false)) {
                    $editLocks = app(TaxonomyEditLocks::class);
                    $hadLock = $editLocks->inspect($type, (int) $record->id) !== null;
                    $lease = $editLocks->acquire($type, (int) $record->id, auth()->user());
                    if (! $lease['acquired']) {
                        return Response::make(false, __('taxonomies.edit_locked', [
                            'user' => $lease['lock']['user_name'] ?? __('taxonomies.unknown_editor'),
                            'time' => LocalizedDate::format($lease['lock']['locked_at'] ?? null),
                        ]), null, 423);
                    }
                    $input['_edit_lock'] = $lease['lock']['token'];
                    $temporaryLock = ! $hadLock;
                }

                try {
                    $result = $this->save($type, $input, $record->id);
                } finally {
                    if ($temporaryLock) {
                        app(TaxonomyEditLocks::class)->release($type, (int) $record->id, (int) auth()->id(), $input['_edit_lock']);
                    }
                }
                if (! $result->ok) {
                    return $result;
                }
            }
        }

        return Response::make(true, __('taxonomies.saved'));
    }

    /**
     * Return a small preview image URL for one taxonomy record, if available.
     *
     * @param TaxonomyModel $record
     *
     * @return string|null
     */
    public function mediaPreview(TaxonomyModel $record): ?string
    {
        if (! Schema::hasTable(config('media.tables.media', 'media')) || ! Schema::hasTable(config('media.tables.media_relation', 'media_relations'))) {
            return null;
        }

        $media = DB::table(config('media.tables.media') . ' as media')
            ->join(config('media.tables.media_relation') . ' as relation', 'relation.media_id', '=', 'media.id')
            ->where('relation.mediaable_type', TaxonomyModel::class)
            ->where('relation.mediaable_id', $record->id)
            ->select('media.uuid', 'media.mime_type')
            ->first();
        if (! $media || ! str_starts_with((string) $media->mime_type, 'image/')) {
            return null;
        }

        return route('media.image.responsive', [
            'uuid' => $media->uuid,
            'w'    => 96,
            'h'    => 96,
            'm'    => 'cover',
        ]);
    }

    /**
     * Return the edit lock details for one taxonomy record, if applicable.
     *
     * @param string $type
     * @param int $id
     *
     * @return array|null
     */
    private function editLockDetails(string $type, int $id): ?array
    {
        if (! TaxonomyTypeRegistry::getOption($type, 'optimistic-locking', false)) {
            return null;
        }

        $lock = app(TaxonomyEditLocks::class)->inspect($type, $id);
        if (! $lock) {
            return null;
        }

        return [
            'user_id'   => (int) ($lock['user_id'] ?? 0),
            'user_name' => (string) ($lock['user_name'] ?? __('taxonomies.unknown_editor')),
            'locked_at' => LocalizedDate::format($lock['locked_at'] ?? null),
            'owned'     => (int) ($lock['user_id'] ?? 0) === (int) auth()->id(),
        ];
    }

    /**
     * Return the media selections for one taxonomy record, grouped by collection.
     *
     * @param TaxonomyModel $record
     *
     * @return array
     */
    public function mediaSelections(TaxonomyModel $record): array
    {
        return DB::table(config('media.tables.media') . ' as media')
            ->join(config('media.tables.media_relation') . ' as relation', 'relation.media_id', '=', 'media.id')
            ->where('relation.mediaable_type', TaxonomyModel::class)
            ->where('relation.mediaable_id', $record->id)
            ->select('media.id', 'media.name', 'media.uuid', 'media.mime_type', 'media.extension', 'relation.collection')
            ->get()
            ->groupBy('collection')
            ->map(fn ($items) => $items->map(fn ($media): array => [
                'id'        => (int) $media->id,
                'name'      => $media->name . ($media->extension ? '.' . $media->extension : ''),
                'mime_type' => $media->mime_type,
                'src'       => str_starts_with((string) $media->mime_type, 'image/') ? route('admin.media-manager.content', $media->id) : null,
            ])
                ->values()
                ->all())
            ->all();
    }

    /**
     * Return the history of changes for one taxonomy record, paginated.
     *
     * @param string $type
     * @param int $id
     * @param array $input
     *
     * @return Response
     * @throws Throwable
     */
    public function history(string $type, int $id, array $input): Response
    {
        $data = dto($input, HistoryRequest::class);
        $this->record($type, $id);

        return app(RecordHistory::class)->page('taxonomies:' . $type, $id, (int) ($data['page'] ?? 1));
    }

    /**
     * Save a translation for one taxonomy record in a specific locale, with history recording.
     *
     * @param string $type
     * @param TaxonomyModel $record
     * @param string $locale
     * @param array $values
     *
     * @return Response
     * @throws Throwable
     */
    public function saveTranslation(string $type, TaxonomyModel $record, string $locale, array $values): Response
    {
        $context = [
            'type'      => $type,
            'id'        => $record->id,
            'operation' => 'update',
            'user_id'   => (int) auth()->id(),
        ];

        DB::transaction(function () use ($type, $record, $locale, $values, $context): void {
            $before = $this->historySnapshot($record);
            Hooks::lifecycle('jobic.taxonomies.saving', $context);
            $record->translation = [$locale => $values];
            $record->save();
            $saved = $this->record($type, (int) $record->id);
            app(RecordHistory::class)->record('taxonomies:' . $type, (int) $record->id, $context['user_id'], 'update', $before, $this->historySnapshot($saved));
        });

        Hooks::notify('jobic.taxonomies.saved', $context);

        return Response::make(true, __('taxonomies.translation_saved'), [
            'locale' => $locale,
            'fields' => $values,
        ]);
    }

    /**
     * Return a snapshot of the record's public state for history comparison.
     *
     * @param TaxonomyModel $record
     *
     * @return array
     */
    private function historySnapshot(TaxonomyModel $record): array
    {
        $state = [
            'translation'   => $record->getTranslations(),
            'parent_id'     => $record->parent_id,
            'ordering'      => $record->ordering,
            'status'        => (bool) $record->status,
            'slug'          => $record->slugByCollection($record->type, true),
            'media'         => $this->mediaPreview($record),
            'custom_fields' => $this->capabilityValues($record)['custom_fields'],
            'seo_index'     => $this->capabilityValues($record)['seo_index'],
        ];

        return Hooks::filter('jobic.taxonomies.history.snapshot', $state, [
            'type'   => $record->type,
            'record' => $record,
        ]);
    }

    /**
     * Return the custom fields and SEO values for one taxonomy record, if applicable.
     *
     * @param TaxonomyModel|null $record
     *
     * @return array[]
     */
    public function capabilityValues(?TaxonomyModel $record): array
    {
        $values = $record?->metas()
            ->whereIn('key', [
                'taxonomy_fields',
                'taxonomy_seo',
                'seo_index',
            ])
            ->pluck('value', 'key') ?? collect();

        $legacySeo = json_decode($values->get('taxonomy_seo', '{}'), true) ?: [];
        $locale = LocaleCatalog::resolve(app()->getLocale());
        $legacyIndex = $legacySeo[$locale]['index'] ?? collect($legacySeo)
            ->pluck('index')
            ->first() ?? 1;

        return [
            'custom_fields' => json_decode($values->get('taxonomy_fields', '{}'), true) ?: [],
            'seo'           => $legacySeo,
            'seo_index'     => $values->has('seo_index') ? (int) $values->get('seo_index') : (int) $legacyIndex,
        ];
    }
}
