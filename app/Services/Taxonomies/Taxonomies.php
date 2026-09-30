<?php

declare(strict_types=1);

namespace App\Services\Taxonomies;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Taxonomy\Facades\Taxonomy;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy as TaxonomyModel;

/** Provide one shared application service for every registered taxonomy type. */
class Taxonomies
{
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

        $result = $id ? Taxonomy::update($id, $payload) : Taxonomy::store($payload);
        if (! ($result['ok'] ?? false)) {
            throw ValidationException::withMessages(['name' => $result['errors'] ?? [$result['message']]]);
        }

        return Response::make(true, __('taxonomies.saved'), ['id' => $result['data']->id], $id ? 200 : 201);
    }

    public function delete(string $type, int $id): Response
    {
        $record = $this->record($type, $id);
        Taxonomy::delete($record->id);

        return Response::make(true, __('taxonomies.deleted'));
    }
}
