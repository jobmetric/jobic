<?php

declare(strict_types=1);

namespace App\Services\Taxonomies;

use App\Facades\Hooks;
use App\Http\Requests\Taxonomies\HistoryRequest;
use App\Services\History\RecordHistory;
use App\Support\LocaleCatalog;
use App\Support\MenuLinks\MenuLinkRegistry;
use App\Support\TaxonomyPresentation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use JobMetric\Media\Facades\Media;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Taxonomy\Facades\Taxonomy;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy as TaxonomyModel;
use JobMetric\Translation\Models\Translation;
use Throwable;

/** Provide one shared application service for every registered taxonomy type. */
class Taxonomies
{
    /** @param array<string, mixed> $input */
    public function table(string $type, array $input): Response
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);
        $builder = TaxonomyTypeRegistry::for($type);
        $query = TaxonomyModel::query()->where('type', $type);
        $total = (clone $query)->count();
        if (($input['status'] ?? '') !== '') {
            $query->where('status', $input['status']);
        }
        if ($term = ($input['search']['value'] ?? null)) {
            $query->whereHas('translations', fn ($translation) => $translation->where('field', 'name')->where('value', 'like', '%'.$term.'%'));
        }
        $filtered = (clone $query)->count();
        $columns = ['name', 'ordering', 'status', 'translations'];
        if ($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty()) {
            array_unshift($columns, 'media');
        }
        $requestedColumn = $columns[max(0, (int) ($input['order'][0]['column'] ?? 1) - 1)] ?? 'ordering';
        $orderColumn = in_array($requestedColumn, ['ordering', 'status'], true) ? $requestedColumn : null;
        $with = ['translations'];
        if ($builder->hasHierarchical()) {
            $with[] = 'paths.path.translations';
        }
        $query->with($with)->offset((int) $input['start'])->limit((int) $input['length']);
        if ($orderColumn) {
            $query->orderBy($orderColumn, $input['order'][0]['dir'] ?? 'asc');
        } else {
            $name = Translation::query()->select('value')
                ->whereColumn('translatable_id', (new TaxonomyModel)->qualifyColumn('id'))
                ->where('translatable_type', TaxonomyModel::class)
                ->where('field', 'name')
                ->where('locale', LocaleCatalog::resolve(app()->getLocale()))
                ->latest('version')->limit(1);
            $query->orderBy($name, $input['order'][0]['dir'] ?? 'asc');
        }
        $rows = $query->orderBy('id')->get()
            ->map(fn (TaxonomyModel $record): array => Hooks::filter('jobic.taxonomies.list.row', [
                'id' => $record->id, 'name' => TaxonomyPresentation::name($record),
                'path_names' => $builder->hasHierarchical() ? $record->paths->sortBy('level')->map(fn ($path): string => TaxonomyPresentation::name($path->path))->values()->all() : [],
                'translations' => $record->getTranslations(),
                'media' => ($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty()) ? $this->mediaPreview($record) : null,
                'ordering' => $record->ordering,
                'status' => $record->status,
            ], ['type' => $type, 'id' => $record->id]))->values();

        return Response::make(true, '', ['draw' => (int) $input['draw'], 'recordsTotal' => $total, 'recordsFiltered' => $filtered, 'data' => $rows]);
    }

    public function type(string $type): array
    {
        return TaxonomyTypeRegistry::get($type);
    }

    /** @return Collection<int, TaxonomyModel> */
    public function records(string $type): Collection
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);

        return TaxonomyModel::query()->with('translations')->where('type', $type)->orderBy('ordering')->orderBy('id')->get();
    }

    /**
     * Return hierarchical parent choices, excluding the edited node and its descendants.
     *
     * @return array<int, array{id:int, name:string, depth:int}>
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
                $options[] = ['id' => (int) $record->id, 'name' => TaxonomyPresentation::name($record), 'depth' => $depth];
                $walk((int) $record->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $options;
    }

    public function record(string $type, int $id): TaxonomyModel
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);

        return TaxonomyModel::query()->with('translations')->where('type', $type)->findOrFail($id);
    }

    /** @param array<string, mixed> $data */
    public function save(string $type, array $data, ?int $id = null): Response
    {
        $builder = TaxonomyTypeRegistry::for($type);
        if ($id !== null) {
            $this->record($type, $id);
        }
        $payload = [
            'type' => $type,
            'ordering' => $data['ordering'],
            'status' => $data['status'],
            'translation' => array_filter($data['translation'], fn (array $fields): bool => collect($fields)->contains(fn ($value) => filled($value))),
        ];
        if ($builder->hasHierarchical()) {
            $payload['parent_id'] = $data['parent_id'] ?? null;
        }
        if ($builder->hasUrl() && filled($data['slug'] ?? null)) {
            $payload['slug'] = $data['slug'];
        }
        $mediaPayload = [];
        foreach ((array) ($data['media_upload'] ?? []) as $collection => $uploads) {
            $ids = [];
            foreach (is_array($uploads) ? $uploads : [$uploads] as $index => $upload) {
                if (! $upload) {
                    continue;
                }
                $field = is_array($uploads) ? "media_upload.$collection.$index" : "media_upload.$collection";
                $uploaded = Media::upload(collection: 'public', field: $field);
                $ids[] = (int) $uploaded['data']->resource->id;
            }
            if ($ids !== []) {
                $multiple = $builder->getMedia()->first(fn ($item) => $item->getCollection() === $collection)?->getMultiple() ?? false;
                $mediaPayload[$collection] = $multiple ? $ids : $ids[0];
            }
        }

        $context = ['type' => $type, 'id' => $id, 'operation' => $id ? 'update' : 'create', 'user_id' => (int) auth()->id()];
        $savedId = DB::transaction(function () use ($type, $id, $payload, $mediaPayload, $context, $data): int {
            $before = $id ? $this->historySnapshot($this->record($type, $id)) : [];
            Hooks::action('jobic.taxonomies.saving', $context);
            $result = $id ? Taxonomy::update($id, $payload) : Taxonomy::store($payload);
            if (! ($result['ok'] ?? false)) {
                throw ValidationException::withMessages(['name' => $result['errors'] ?? [$result['message']]]);
            }

            $savedId = (int) $result['data']->id;
            foreach ($mediaPayload as $collection => $mediaIds) {
                DB::table(config('media.tables.media_relation'))->where([
                    'mediaable_type' => TaxonomyModel::class, 'mediaable_id' => $savedId, 'collection' => $collection,
                ])->delete();
                foreach ((array) $mediaIds as $mediaId) {
                    DB::table(config('media.tables.media_relation'))->insert([
                        'media_id' => $mediaId, 'mediaable_type' => TaxonomyModel::class,
                        'mediaable_id' => $savedId, 'collection' => $collection, 'created_at' => now(),
                    ]);
                }
            }
            $saved = $this->record($type, $savedId);
            if (preg_match('/^menu_\d+$/', $type) === 1 && array_key_exists('menu_link_driver', $data)) {
                $driverKey = (string) $data['menu_link_driver'];
                $driver = app(MenuLinkRegistry::class)->get($driverKey);
                $linkData = collect(array_keys($driver->rules()))->mapWithKeys(fn (string $key): array => [$key => $data[$key] ?? null])->all();
                $saved->metas()->updateOrCreate(['key' => 'menu_link_driver'], ['value' => $driverKey, 'is_json' => false]);
                $saved->metas()->updateOrCreate(['key' => 'menu_link_data'], ['value' => json_encode($linkData, JSON_UNESCAPED_UNICODE), 'is_json' => true]);
            }
            app(RecordHistory::class)->record('taxonomies:'.$type, $savedId, $context['user_id'], $context['operation'], $before, $this->historySnapshot($saved));

            return $savedId;
        });
        if ($type === 'menu') {
            $saved = $this->record($type, $savedId);
            TaxonomyTypeRegistry::register('menu_'.$savedId)->apply(['back-url' => url('/admin/taxonomies/menu')])->label(TaxonomyPresentation::name($saved))->hierarchical()->baseMedia()->changeStatusInList();
        }
        Hooks::notify('jobic.taxonomies.saved', array_replace($context, ['id' => $savedId]));

        return Response::make(true, __('taxonomies.saved'), ['id' => $savedId], $id ? 200 : 201);
    }

    public function delete(string $type, int $id): Response
    {
        $record = $this->record($type, $id);
        $before = $this->historySnapshot($record);
        $context = ['type' => $type, 'id' => $id, 'operation' => 'delete', 'user_id' => (int) auth()->id()];
        Hooks::action('jobic.taxonomies.deleting', $context);
        DB::transaction(function () use ($record, $type, $before, $context): void {
            if ($type === 'menu') {
                TaxonomyModel::query()->where('type', 'menu_'.$record->id)->get()->each(fn (TaxonomyModel $item) => Taxonomy::delete($item->id));
                TaxonomyTypeRegistry::unregister('menu_'.$record->id);
            }
            Taxonomy::delete($record->id);
            app(RecordHistory::class)->record('taxonomies:'.$type, $record->id, $context['user_id'], 'delete', $before, []);
        });
        Hooks::notify('jobic.taxonomies.deleted', $context);

        return Response::make(true, __('taxonomies.deleted'));
    }

    /** @param array<string, mixed> $data */
    public function bulk(string $type, array $data): Response
    {
        foreach ($data['ids'] as $id) {
            if ($data['operation'] === 'delete') {
                $this->delete($type, (int) $id);
            } else {
                $record = $this->record($type, (int) $id);
                $this->save($type, ['translation' => $record->getTranslations(), 'ordering' => $record->ordering, 'status' => $data['operation'] === 'activate', 'parent_id' => $record->parent_id], $record->id);
            }
        }

        return Response::make(true, __('taxonomies.saved'));
    }

    public function mediaPreview(TaxonomyModel $record): ?string
    {
        if (! Schema::hasTable(config('media.tables.media', 'media')) || ! Schema::hasTable(config('media.tables.media_relation', 'media_relations'))) {
            return null;
        }

        $media = DB::table(config('media.tables.media').' as media')
            ->join(config('media.tables.media_relation').' as relation', 'relation.media_id', '=', 'media.id')
            ->where('relation.mediaable_type', TaxonomyModel::class)->where('relation.mediaable_id', $record->id)
            ->select('media.uuid', 'media.mime_type')->first();
        if (! $media || ! str_starts_with((string) $media->mime_type, 'image/')) {
            return null;
        }

        return route('media.image.responsive', ['uuid' => $media->uuid, 'w' => 96, 'h' => 96, 'm' => 'cover']);
    }

    /**
     * Validate and return a bounded history page for one taxonomy record.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws Throwable
     */
    public function history(string $type, int $id, array $input): Response
    {
        $data = dto($input, HistoryRequest::class);
        $this->record($type, $id);

        return app(RecordHistory::class)->page('taxonomies:'.$type, $id, (int) ($data['page'] ?? 1));
    }

    /**
     * Persist one locale and record its public translation changes.
     *
     * @param  array<string, mixed>  $values
     *
     * @throws Throwable
     */
    public function saveTranslation(string $type, TaxonomyModel $record, string $locale, array $values): Response
    {
        $context = ['type' => $type, 'id' => $record->id, 'operation' => 'update', 'user_id' => (int) auth()->id()];
        DB::transaction(function () use ($type, $record, $locale, $values, $context): void {
            $before = $this->historySnapshot($record);
            Hooks::action('jobic.taxonomies.saving', $context);
            $record->translation = [$locale => $values];
            $record->save();
            $saved = $this->record($type, (int) $record->id);
            app(RecordHistory::class)->record('taxonomies:'.$type, (int) $record->id, $context['user_id'], 'update', $before, $this->historySnapshot($saved));
        });
        Hooks::notify('jobic.taxonomies.saved', $context);

        return Response::make(true, __('taxonomies.translation_saved'), ['locale' => $locale, 'fields' => $values]);
    }

    /**
     * Build the safe public taxonomy state stored in change history.
     *
     *
     * @return array<string, mixed>
     */
    private function historySnapshot(TaxonomyModel $record): array
    {
        $driver = $record->metas()->where('key', 'menu_link_driver')->value('value');
        $linkData = $record->metas()->where('key', 'menu_link_data')->first();

        return [
            'translation' => $record->getTranslations(),
            'parent_id' => $record->parent_id,
            'ordering' => $record->ordering,
            'status' => (bool) $record->status,
            'slug' => $record->slugByCollection($record->type, true),
            'media' => $this->mediaPreview($record),
            'link' => $driver ? ['driver' => $driver, 'data' => $linkData?->is_json ? json_decode((string) $linkData->value, true) : []] : null,
        ];
    }
}
