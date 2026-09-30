<?php

declare(strict_types=1);

namespace App\Services\Taxonomies;

use App\Facades\Hooks;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Taxonomy\Facades\Taxonomy;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy as TaxonomyModel;

/** Provide one shared application service for every registered taxonomy type. */
class Taxonomies
{
    /** @param array<string, mixed> $input */
    public function table(string $type, array $input): Response
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);
        $query = TaxonomyModel::query()->where('type', $type);
        $total = (clone $query)->count();
        if (($input['status'] ?? '') !== '') {
            $query->where('status', $input['status']);
        }
        if ($term = ($input['search']['value'] ?? null)) {
            $query->whereHas('translations', fn ($translation) => $translation->where('field', 'name')->where('value', 'like', '%'.$term.'%'));
        }
        $filtered = (clone $query)->count();
        $columns = [1 => 'id', 2 => 'ordering', 3 => 'status', 4 => 'created_at'];
        $rows = $query->with('translations')->offset((int) $input['start'])->limit((int) $input['length'])
            ->orderBy($columns[$input['order'][0]['column'] ?? 2], $input['order'][0]['dir'] ?? 'asc')->orderBy('id')->get()
            ->map(fn (TaxonomyModel $record): array => Hooks::filter('jobic.taxonomies.list.row', [
                'id' => $record->id, 'name' => $record->getTranslation('name'), 'ordering' => $record->ordering,
                'status' => $record->status, 'created_at' => $record->created_at?->toISOString(),
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
            'translation' => [app()->getLocale() => ['name' => $data['name']]],
        ];
        if ($builder->hasHierarchical()) {
            $payload['parent_id'] = $data['parent_id'] ?? null;
        }
        if ($builder->hasUrl() && filled($data['slug'] ?? null)) {
            $payload['slug'] = $data['slug'];
        }

        $context = ['type' => $type, 'id' => $id, 'operation' => $id ? 'update' : 'create', 'user_id' => (int) auth()->id()];
        Hooks::action('jobic.taxonomies.saving', $context);
        $result = $id ? Taxonomy::update($id, $payload) : Taxonomy::store($payload);
        if (! ($result['ok'] ?? false)) {
            throw ValidationException::withMessages(['name' => $result['errors'] ?? [$result['message']]]);
        }

        $savedId = (int) $result['data']->id;
        if ($type === 'menu') {
            TaxonomyTypeRegistry::register('menu_'.$savedId)->label($data['name'])->hierarchical()->changeStatusInList();
        }
        Hooks::notify('jobic.taxonomies.saved', array_replace($context, ['id' => $savedId]));

        return Response::make(true, __('taxonomies.saved'), ['id' => $savedId], $id ? 200 : 201);
    }

    public function delete(string $type, int $id): Response
    {
        $record = $this->record($type, $id);
        $context = ['type' => $type, 'id' => $id, 'operation' => 'delete', 'user_id' => (int) auth()->id()];
        Hooks::action('jobic.taxonomies.deleting', $context);
        DB::transaction(function () use ($record, $type): void {
            if ($type === 'menu') {
                TaxonomyModel::query()->where('type', 'menu_'.$record->id)->get()->each(fn (TaxonomyModel $item) => Taxonomy::delete($item->id));
                TaxonomyTypeRegistry::unregister('menu_'.$record->id);
            }
            Taxonomy::delete($record->id);
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
                $this->save($type, ['name' => $record->getTranslation('name'), 'ordering' => $record->ordering, 'status' => $data['operation'] === 'activate', 'parent_id' => $record->parent_id], $record->id);
            }
        }

        return Response::make(true, __('taxonomies.saved'));
    }
}
