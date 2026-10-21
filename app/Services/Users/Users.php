<?php

declare(strict_types=1);

namespace App\Services\Users;

use App\Events\User\UserActivateEvent;
use App\Events\User\UserDeactivateEvent;
use App\Events\User\UserDeleteEvent;
use App\Events\User\UserStoreEvent;
use App\Events\User\UserUpdateEvent;
use App\Facades\Hooks;
use App\Http\Requests\Users\BulkRequest;
use App\Http\Requests\Users\HistoryRequest;
use App\Http\Requests\Users\TableRequest;
use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\History\RecordHistory;
use App\Support\RecordVersion;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JobMetric\EventSystem\Support\CommittedEventDispatcher;
use JobMetric\PackageCore\Output\Response;
use JobMetric\PackageCore\Services\AbstractCrudService;
use JobMetric\Rolix\Facades\Role as RoleService;
use Throwable;

/**
 * Manage administration users through validated, transactional CRUD operations.
 */
class Users extends AbstractCrudService
{
    protected static string $modelClass = User::class;

    protected static string $resourceClass = UserResource::class;

    /**
     * Return ordered system-role choices for membership editing.
     *
     * @return array<int, array{id: int, name: string, path_label: string, depth: int}>
     */
    public function roleOptions(): array
    {
        return RoleService::flatTree('system')
            ->map(fn ($role) => [
                'id'         => (int) $role->id,
                'name'       => (string) $role->name,
                'path_label' => (string) $role->getAttribute('path_label'),
                'depth'      => (int) $role->getAttribute('depth'),
            ])
            ->values()
            ->all();
    }

    /**
     * Read a bounded user table page with real unfiltered statistics.
     *
     * @param array<string, mixed> $input
     *
     * @return Response
     * @throws Throwable
     */
    public function table(array $input): Response
    {
        $data = dto($input, TableRequest::class);

        $query = User::query();
        $total = (clone $query)->count();
        $stats = [
            $total,
            (clone $query)->where('status', true)
                ->count(),
            (clone $query)->whereNotNull('email_verified_at')
                ->count(),
            (clone $query)->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        if (isset($data['status']) && $data['status'] !== '') {
            $query->where('status', $data['status']);
        }

        if (isset($data['verified']) && $data['verified'] !== '') {
            $data['verified'] ? $query->whereNotNull('email_verified_at') : $query->whereNull('email_verified_at');
        }

        if ($term = ($data['search']['value'] ?? null)) {
            $query->where(fn ($builder) => $builder->where('name', 'like', '%' . $term . '%')
                ->orWhere('email', 'like', '%' . $term . '%'));
        }

        $filtered = (clone $query)->count();
        $columns = [
            1 => 'name',
            2 => 'email',
            3 => 'status',
            4 => 'email_verified_at',
            5 => 'created_at',
        ];
        $query->orderBy($columns[$data['order'][0]['column'] ?? 1], $data['order'][0]['dir'] ?? 'asc')
            ->orderBy('id');

        return Response::make(true, '', [
            'draw'            => (int) $data['draw'],
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => UserResource::collection($query->offset((int) $data['start'])
                ->limit((int) $data['length'])
                ->get())
                ->resolve(),
            'stats'           => $stats,
        ]);
    }

    /**
     * Find a user for editing or read-only presentation.
     *
     * @param int $id
     *
     * @return User
     * @throws ModelNotFoundException
     */
    public function record(int $id): User
    {
        return User::query()
            ->findOrFail($id);
    }

    /**
     * Validate and persist one user without exposing its password to hooks or history.
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
        $data = dto($input, UserRequest::class);

        $data['email'] = Str::lower($data['email']);
        $context = [
            'user_id'   => $actorId,
            'id'        => $id,
            'operation' => $id ? 'update' : 'create',
        ];
        $version = null;

        $record = DB::transaction(function () use ($data, $id, $actorId, $context, &$version) {
            $all = User::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $record = $id ? $all->firstWhere('id', $id) : new User;
            if (! $record) {
                throw (new ModelNotFoundException)->setModel(User::class, [$id]);
            }

            if ($id && ! hash_equals(RecordVersion::token($record, 'users'), (string) $data['_version'])) {
                return Response::make(false, __('users.conflict'), null, 409);
            }

            unset($data['_version'], $data['password_confirmation']);
            $roleIds = array_map('intval', $data['role_ids'] ?? []);
            unset($data['role_ids']);
            if (($id === $actorId || $all->where('status', true)
                        ->where('id', '!=', $id)
                        ->isEmpty()) && ! $data['status']) {
                throw ValidationException::withMessages(['status' => $id === $actorId ? __('users.current_status') : __('users.last_active')]);
            }

            if (User::query()
                ->where('email', $data['email'])
                ->when($id, fn ($builder) => $builder->whereKeyNot($id))
                ->exists()) {
                throw ValidationException::withMessages(['email' => __('users.duplicate')]);
            }

            if (($data['password'] ?? null) === null || $data['password'] === '') {
                unset($data['password']);
            }

            $before = $record->exists ? array_replace($record->toArray(), [
                'role_ids' => $record->memberships()
                    ->whereNull('memberable_type')
                    ->whereNull('memberable_id')
                    ->pluck('role_id')
                    ->map(fn ($roleId) => (int) $roleId)
                    ->sort()
                    ->values()
                    ->all(),
            ]) : [];

            Hooks::lifecycle('jobic.users.saving', $context);

            try {
                $record->fill($data);
                if ($record->exists && $record->isDirty('email')) {
                    $record->email_verified_at = null;
                }
                $record->save();
                $record->syncRoles($roleIds);
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['email' => __('users.duplicate')]);
            }

            $after = array_replace($record->toArray(), [
                'role_ids' => collect($roleIds)
                    ->sort()
                    ->values()
                    ->all(),
            ]);
            app(RecordHistory::class)->record('users', (int) $record->id, $actorId, $context['operation'], $before, $after);

            $changed = (! $id && $record->exists) || array_diff(array_keys($record->getChanges()), ['updated_at']) !== [] || ($before['role_ids'] ?? []) !== $after['role_ids'];
            if ($changed) {
                $event = $id ? new UserUpdateEvent((int) $record->id, $actorId) : new UserStoreEvent((int) $record->id, $actorId);
                app(CommittedEventDispatcher::class)->dispatch($event, $record->getConnectionName());
            }

            $version = RecordVersion::token($record->fresh(), 'users');

            return $record;
        });

        if ($record instanceof Response) {
            return $record;
        }

        Hooks::notify('jobic.users.saved', array_replace($context, ['id' => $record->id]));

        return Response::make(true, __('users.saved'), [
            'version'  => $version,
            'id'       => $record->id,
            'edit_url' => route('admin.users.edit', $record->id),
            'save_url' => route('admin.users.update', $record->id),
        ], $id ? 200 : 201);
    }

    /**
     * Apply an atomic status or deletion operation to selected users.
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
            $all = User::query()
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $records = $all->whereIn('id', $data['ids']);

            if ($records->count() !== count($data['ids'])) {
                throw ValidationException::withMessages(['ids' => __('users.missing')]);
            }

            if ($data['operation'] !== 'activate' && in_array($actorId, $data['ids'], true)) {
                throw ValidationException::withMessages(['ids' => __('users.current_account')]);
            }

            if ($data['operation'] !== 'activate' && $all->where('status', true)
                    ->whereNotIn('id', $data['ids'])
                    ->isEmpty()) {
                throw ValidationException::withMessages(['ids' => __('users.last_active')]);
            }

            $contexts = [];
            foreach ($records as $record) {
                $context = [
                    'user_id'   => $actorId,
                    'id'        => $record->id,
                    'operation' => $data['operation'],
                ];
                $deleting = $data['operation'] === 'delete';

                Hooks::lifecycle('jobic.users.' . ($deleting ? 'deleting' : 'saving'), $context);

                $changed = $deleting || (bool) $record->status !== ($data['operation'] === 'activate');
                $before = $record->toArray();
                if ($deleting) {
                    $record->delete();
                } else {
                    $record->forceFill(['status' => $data['operation'] === 'activate'])
                        ->save();
                }

                app(RecordHistory::class)->record('users', (int) $record->id, $actorId, $data['operation'], $before, $deleting ? [] : $record->fresh()
                    ->toArray());

                if ($changed && ($deleting ? ! $record->exists : $record->wasChanged('status'))) {
                    $eventClass = match ($data['operation']) {
                        'delete' => UserDeleteEvent::class,
                        'activate' => UserActivateEvent::class,
                        'deactivate' => UserDeactivateEvent::class,
                    };
                    app(CommittedEventDispatcher::class)->dispatch(new $eventClass((int) $record->id, $actorId), $record->getConnectionName());
                }

                $contexts[] = $context;
            }

            return $contexts;
        });

        foreach ($contexts as $context) {
            Hooks::notify('jobic.users.' . ($data['operation'] === 'delete' ? 'deleted' : 'saved'), $context);
        }

        return Response::make(true, __('users.saved'));
    }

    /**
     * Validate and return one existing user's change history.
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

        return app(RecordHistory::class)->page('users', $id, (int) ($data['page'] ?? 1));
    }
}
