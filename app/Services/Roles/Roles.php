<?php

declare(strict_types=1);

namespace App\Services\Roles;

use App\Facades\Hooks;
use App\Http\Requests\Roles\BulkRequest;
use App\Http\Requests\Roles\HistoryRequest;
use App\Http\Requests\Roles\RoleRequest;
use App\Http\Requests\Roles\TableRequest;
use App\Services\History\RecordHistory;
use App\Support\RecordVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\PackageCore\Services\AbstractCrudService;
use JobMetric\Rolix\Facades\Role as RoleService;
use JobMetric\Rolix\Http\Resources\RoleResource;
use JobMetric\Rolix\Models\Role;
use Throwable;

/**
 * Manage system roles through Rolix while enforcing CMS table and extension contracts.
 */
class Roles extends AbstractCrudService
{
    protected static string $modelClass = Role::class;

    protected static string $resourceClass = RoleResource::class;

    /**
     * Read a bounded system-role table page with real unfiltered statistics.
     *
     * @param array<string, mixed> $input
     *
     * @return Response
     * @throws Throwable
     */
    public function table(array $input): Response
    {
        $data = dto($input, TableRequest::class);

        $query = Role::query()->where('type', 'system');
        $total = (clone $query)->count();
        $stats = [
            $total,
            (clone $query)->where('is_default', true)->count(),
            (clone $query)->where('is_super', true)->count(),
            (clone $query)->whereNotNull('parent_id')->count(),
        ];

        $records = RoleService::flatTree('system');
        $kind = $data['kind'] ?? '';
        $records = $records->filter(static fn (Role $role) => match ($kind) {
            'default' => (bool) $role->is_default,
            'super' => (bool) $role->is_super,
            'regular' => ! $role->is_default && ! $role->is_super,
            default => true,
        });

        if ($term = mb_strtolower((string) ($data['search']['value'] ?? ''))) {
            $records = $records->filter(static fn (Role $role
            ) => str_contains(mb_strtolower((string) $role->name), $term) || str_contains(mb_strtolower((string) $role->description), $term));
        }

        $filtered = $records->count();
        $column = (int) ($data['order'][0]['column'] ?? 4);
        $direction = $data['order'][0]['dir'] ?? 'asc';

        if ($column !== 1) {
            $keys = [2 => 'is_default', 3 => 'is_super', 4 => 'ordering'];
            $records = $records->sortBy($keys[$column] ?? 'ordering', SORT_REGULAR, $direction === 'desc');
        } else {
            if ($direction === 'desc') {
                $records = $records->reverse();
            }
        }

        $records = $records->slice((int) $data['start'], (int) $data['length'])->map(fn (Role $role) => [
            'id'         => $role->id,
            'name'       => $role->name,
            'path_label' => $role->getAttribute('path_label'),
            'depth'      => (int) $role->getAttribute('depth'),
            'is_default' => (bool) $role->is_default,
            'is_super'   => (bool) $role->is_super,
            'ordering'   => $role->ordering,
        ])->values()->all();

        return Response::make(true, '', [
            'draw'            => (int) $data['draw'],
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $records,
            'stats'           => $stats,
        ]);
    }

    /**
     * Find a system role with its rule definitions.
     *
     * @param int $id
     *
     * @return Role
     * @throws ModelNotFoundException
     */
    public function record(int $id): Role
    {
        return Role::query()->where('type', 'system')->with('rules')->findOrFail($id);
    }

    /**
     * Return ordered parent choices excluding the edited role and its descendants.
     *
     * @param int|null $exceptId
     *
     * @return array<int, array{id: int, name: string, depth: int}>
     */
    public function parentOptions(?int $exceptId = null): array
    {
        $excluded = $exceptId ? $this->record($exceptId)->descendants()->pluck('id')->push($exceptId)->all() : [];

        return RoleService::flatTree('system')
            ->reject(fn (Role $role) => in_array($role->id, $excluded, true))
            ->map(fn (Role $role) => [
                'id'    => (int) $role->id,
                'name'  => (string) $role->name,
                'depth' => (int) $role->getAttribute('depth'),
            ])
            ->values()
            ->all();
    }

    /**
     * Validate and persist a system role through the Rolix service.
     *
     * @param array<string, mixed> $input
     * @param int|null $id
     * @param int $actorId
     *
     * @return Response
     * @throws Throwable
     */
    public function saveRecord(array $input, ?int $id, int $actorId): Response
    {
        $data = dto($input, RoleRequest::class);

        $context = [
            'user_id'   => $actorId,
            'id'        => $id,
            'operation' => $id ? 'update' : 'create',
        ];
        $version = null;

        $record = DB::transaction(function () use ($data, $id, $actorId, $context, &$version) {
            $existing = $id ? Role::query()->where('type', 'system')->lockForUpdate()->find($id) : null;
            if ($id && ! $existing) {
                throw (new ModelNotFoundException)->setModel(Role::class, [$id]);
            }

            if ($existing && ! hash_equals(RecordVersion::token($existing, 'roles'), (string) $data['_version'])) {
                return Response::make(false, __('roles.conflict'), null, 409);
            }

            unset($data['_version']);
            $data['type'] = 'system';
            $data['rules'] ??= [];
            $before = $existing?->toArray() ?? [];

            Hooks::action('jobic.roles.saving', $context);

            $result = $id ? RoleService::update($id, $data) : RoleService::store($data);
            if (! $result->ok) {
                throw ValidationException::withMessages(['name' => $result->message]);
            }

            /** @var Role $saved */
            $saved = $result->data->resource;
            $saved = $saved->fresh(['rules']);
            app(RecordHistory::class)->record('roles', (int) $saved->id, $actorId, $context['operation'], $before, $saved->toArray());
            $version = RecordVersion::token($saved, 'roles');

            return $saved;
        });

        if ($record instanceof Response) {
            return $record;
        }

        Hooks::notify('jobic.roles.saved', array_replace($context, ['id' => $record->id]));

        return Response::make(true, __('roles.saved'), [
            'version'  => $version,
            'id'       => $record->id,
            'edit_url' => route('admin.roles.edit', $record->id),
            'save_url' => route('admin.roles.update', $record->id),
        ], $id ? 200 : 201);
    }

    /**
     * Delete selected non-super system roles atomically through Rolix.
     *
     * @param array<string, mixed> $input
     * @param int $actorId
     *
     * @return Response
     * @throws Throwable
     */
    public function bulk(array $input, int $actorId): Response
    {
        $data = dto($input, BulkRequest::class);
        $contexts = DB::transaction(function () use ($data, $actorId) {
            $records = Role::query()
                ->where('type', 'system')
                ->whereIn('id', $data['ids'])
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($records->count() !== count($data['ids'])) {
                throw ValidationException::withMessages([
                    'ids' => __('roles.missing'),
                ]);
            }

            if ($records->contains('is_super', true)) {
                throw ValidationException::withMessages([
                    'ids' => __('roles.super_protected'),
                ]);
            }

            $contexts = [];
            foreach ($records as $record) {
                $context = [
                    'user_id'   => $actorId,
                    'id'        => $record->id,
                    'operation' => 'delete',
                ];

                Hooks::action('jobic.roles.deleting', $context);

                $before = $record->toArray();
                $result = RoleService::destroy((int) $record->id);
                if (! $result->ok) {
                    throw ValidationException::withMessages(['ids' => $result->message]);
                }

                app(RecordHistory::class)->record('roles', (int) $record->id, $actorId, 'delete', $before, []);

                $contexts[] = $context;
            }

            return $contexts;
        });

        foreach ($contexts as $context) {
            Hooks::notify('jobic.roles.deleted', $context);
        }

        return Response::make(true, __('roles.saved'));
    }

    /**
     * Validate and return one role's change history.
     *
     * @param int $id
     * @param array<string, mixed> $input
     *
     * @return Response
     * @throws Throwable
     */
    public function history(int $id, array $input): Response
    {
        $data = dto($input, HistoryRequest::class);
        $this->record($id);

        return app(RecordHistory::class)->page('roles', $id, (int) ($data['page'] ?? 1));
    }
}
