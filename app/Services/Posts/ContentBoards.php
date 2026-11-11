<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Http\Requests\Posts\ContentBoardBulkRequest;
use App\Http\Requests\Posts\ContentBoardReadRequest;
use App\Http\Requests\Posts\ContentBoardRequest;
use App\Models\ContentBoard;
use App\Models\User;
use App\Services\History\RecordHistory;
use App\Support\AdminAccess;
use App\Support\KanbanFilterRegistry;
use App\Support\LocalizedDate;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\PackageCore\Services\AbstractCrudService;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use JobMetric\Rolix\Models\Role;

/** Manage type-scoped board definitions without granting content or transition privileges. */
class ContentBoards extends AbstractCrudService
{
    protected static string $modelClass = ContentBoard::class;

    protected static string $resourceClass = JsonResource::class;

    protected array $excepts = ['query', 'paginate', 'all', 'show', 'store', 'update', 'destroy', 'restore', 'forceDelete'];

    /**
     * Register the independent board management permission.
     *
     * @param string $type
     *
     * @return string
     */
    public function permission(string $type): string
    {
        $key = (PostTypeRegistry::for($type)->getManagePermission() ?? 'admin.content.post_type_'.Str::snake($type).'.manage').'.kanban';
        $registry = app(PermissionRegistry::class);
        if (! $registry->has($key)) {
            $registry->addPermission($key, 'kanban.manage_boards', 'admin.posts.kanban.index', ['type' => $type]);
        }

        return $key;
    }

    /**
     * Resolve management through the shared inherited and explicit-deny decision.
     *
     * @param string $type
     * @param User $actor
     *
     * @return bool
     */
    public function mayManage(string $type, User $actor): bool
    {
        return app(AdminAccess::class)->allows($actor, $this->permission($type));
    }

    /**
     * Apply the role-or-user board audience without bypassing content access.
     *
     * @param Builder $query
     * @param User $actor
     *
     * @return Builder
     */
    public function visible(Builder $query, User $actor): Builder
    {
        $roles = app(PostStateAccess::class)->effectiveSystemRoleIdsFor($actor);

        return $query->where(function ($q) use ($roles, $actor): void {
            $q->where(fn ($open) => $open->whereJsonLength('roles', 0)->whereJsonLength('users', 0))->orWhereJsonContains('users', (int) $actor->id);
            foreach ($roles as $id) {
                $q->orWhereJsonContains('roles', (int) $id);
            }
        });
    }

    /**
     * Resolve an accessible custom board and enforce opt-in capability.
     *
     * @param string $type
     * @param int $id
     * @param User $actor
     *
     * @return ContentBoard
     * @throws \Throwable
     */
    public function accessible(string $type, int $id, User $actor): ContentBoard
    {
        abort_unless(PostTypeRegistry::for($type)->hasMultipleKanban(), 404);
        $board = ContentBoard::where('type', $type)->findOrFail($id);
        abort_unless($this->visible(ContentBoard::whereKey($id), $actor)->exists(), 403);

        return $board;
    }

    /**
     * Present safe directory metadata and the independently authorized management catalog.
     *
     * @param string $type
     * @param User $actor
     * @param bool $withForm
     *
     * @return array<string, mixed>
     */
    public function page(string $type, User $actor, bool $withForm = false): array
    {
        $manage = $this->mayManage($type, $actor);
        $definition = app(PostLifecycleView::class)->activeDefinition($type);

        return ['copy' => trans('kanban'), 'manage' => $manage, 'backUrl' => route('admin.posts.index', ['type' => $type]),
            'dataUrl' => route('admin.posts.kanban.boards.data', ['type' => $type]), 'saveUrl' => route('admin.posts.kanban.boards.store', ['type' => $type]), 'createUrl'=>route('admin.posts.kanban.boards.create',['type'=>$type]),
            'baseUrl' => route('admin.posts.kanban.index', ['type' => $type]), 'defaultUrl' => route('admin.posts.kanban.default', ['type' => $type]),
            'states' => collect($definition?->graph['states'] ?? [])->map(fn ($s) => ['id' => $s['status'], 'name' => $s['translations'][app()->getLocale()]['name'] ?? $s['name']])->values()->all(),
            'roles' => $manage ? Role::where('type', 'system')->orderBy('name')->get(['id', 'name'])->toArray() : [],
            'users' => $manage ? User::orderBy('name')->get(['id', 'name'])->toArray() : [],
            'drivers' => $manage && $withForm ? app(KanbanFilterRegistry::class)->catalog($type) : []];
    }

    /**
     * Prepare an independently addressed management form without granting board viewing access.
     *
     * @param string $type
     * @param int|null $id
     * @param User $actor
     *
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function formPage(string $type, ?int $id, User $actor): array
    {
        abort_unless($this->mayManage($type,$actor),403);
        $record=$id ? ContentBoard::where('type',$type)->findOrFail($id) : null;
        return ['record'=>$record,'config'=>$this->page($type,$actor,true)+['board'=>$record ? $record->only(['id','name','version','roles','users','states','filters']) : ['id'=>null,'name'=>'','version'=>0,'roles'=>[],'users'=>[],'states'=>[],'filters'=>[]]]];
    }

    /**
     * Return a bounded directory with audience enforcement or explicit management mode.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param User $actor
     * @param bool|null $management
     *
     * @return Response
     * @throws \Throwable
     */
    public function listing(string $type, array $data, User $actor, ?bool $management = null): Response
    {
        $data = dto($data, ContentBoardReadRequest::class);
        $management ??= $this->mayManage($type, $actor);
        if ($management) {
            abort_unless($this->mayManage($type, $actor), 403);
        }
        $query = ContentBoard::where('type', $type);
        if (! $management) {
            $this->visible($query, $actor);
        }
        $total = (clone $query)->count();
        $open = (clone $query)->whereJsonLength('roles', 0)->whereJsonLength('users', 0)->count();
        $recent = (clone $query)->where('created_at', '>=', now()->subDays(30))->count();
        if (! empty($data['search'])) {
            $query->where('name', 'like', '%'.addcslashes($data['search'], '%_\\').'%');
        }
        if (isset($data['audience']) && $data['audience'] !== null && $data['audience'] !== '') {
            if ($data['audience'] === 'open') $query->whereJsonLength('roles', 0)->whereJsonLength('users', 0);
            else $query->where(fn ($scope) => $scope->whereJsonLength('roles', '>', 0)->orWhereJsonLength('users', '>', 0));
        }
        if (isset($data['has_filters']) && $data['has_filters'] !== '') {
            $query->whereJsonLength('filters', (int) $data['has_filters'] === 1 ? '>' : '=', 0);
        }
        foreach (['roles', 'users', 'states'] as $field) {
            $values = $data[$field] ?? [];
            if ($values === []) continue;
            if ($field !== 'states') abort_unless($management, 403);
            $query->where(function ($scope) use ($field, $values): void {
                foreach ($values as $value) $scope->orWhereJsonContains($field, $field === 'states' ? $value : (int) $value);
            });
        }
        $filtered = (clone $query)->count();
        $rows = $query->orderBy($data['sort'] ?? 'name', $data['direction'] ?? 'asc')->orderBy('id')->offset($data['start'] ?? 0)->limit($data['length'] ?? 20)->get();

        return Response::make(true, '', ['stats' => [$total, $open, $total - $open, $recent], 'total' => $total, 'filtered' => $filtered, 'items' => $rows->map(function ($board) use ($type, $actor, $management) {
            $visible = $this->visible(ContentBoard::whereKey($board->id), $actor)->exists();
            $row = ['id' => $board->id, 'name' => $board->name, 'version' => $board->version, 'edit_url' => $management ? route('admin.posts.kanban.boards.edit', ['type'=>$type,'board'=>$board->id]) : null, 'updated_at' => LocalizedDate::format($board->updated_at), 'url' => $visible ? route('admin.posts.kanban.board', ['type' => $type, 'board' => $board->id]) : null];

            return $management ? $row + ['roles' => $board->roles, 'users' => $board->users, 'states' => $board->states, 'filters' => $board->filters] : $row;
        })->all()]);
    }

    /**
     * Save validated settings under workflow and board locks with optimistic concurrency.
     *
     * @param string $type
     * @param int|null $id
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws ValidationException
     */
    public function save(string $type, ?int $id, array $data, User $actor): Response
    {
        abort_unless(PostTypeRegistry::for($type)->hasMultipleKanban(), 404);
        abort_unless($this->mayManage($type, $actor), 403);
        $data = dto($data, ContentBoardRequest::class);

        return DB::transaction(function () use ($type, $id, $data, $actor): Response {
            app(PostWorkflowDefinitions::class)->lock($type);
            $definition = app(PostLifecycleView::class)->activeDefinition($type);
            $statuses = array_column($definition?->graph['states'] ?? [], 'status');
            if (array_diff($data['states'], $statuses) !== []) {
                throw ValidationException::withMessages(['states' => __('kanban.invalid_states')]);
            }
            $data['filters'] = app(KanbanFilterRegistry::class)->validate($type, $data['filters']);
            $board = $id ? ContentBoard::where('type', $type)->lockForUpdate()->findOrFail($id) : new ContentBoard(['type' => $type]);
            abort_if((int) $data['version'] !== ($id ? $board->version : 0), 409, __('kanban.stale'));
            $context = ['type' => $type, 'id' => $id, 'user_id' => (int) $actor->id, 'operation' => $id ? 'update' : 'create'];
            $before = $board->exists ? $board->toArray() : [];
            Hooks::lifecycle('jobic.posts.kanban.boards.saving', $context);
            $board->fill(['name' => trim($data['name']), 'roles' => array_map('intval', $data['roles']), 'users' => array_map('intval', $data['users']),
                'states' => $data['states'], 'filters' => $data['filters'], 'version' => ($id ? $board->version : 0) + 1]);
            $board->save();
            $context['id'] = (int) $board->id;
            app(RecordHistory::class)->record('content_boards', (int) $board->id, (int) $actor->id, $context['operation'], $before, $board->toArray());
            DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.boards.saved', $context));
            app(\App\Support\KanbanRealtime::class)->changed($type);

            return Response::make(true, __('kanban.saved'), ['board' => $board->fresh()->toArray(), 'edit_url'=>route('admin.posts.kanban.boards.edit',['type'=>$type,'board'=>$board->id]), 'save_url'=>route('admin.posts.kanban.boards.update',['type'=>$type,'board'=>$board->id])]);
        });
    }

    /**
     * Delete only a custom board and its cascading layout records.
     *
     * @param string $type
     * @param int $id
     * @param int $version
     * @param User $actor
     *
     * @return Response
     * @throws \Throwable
     */
    public function delete(string $type, int $id, int $version, User $actor): Response
    {
        abort_unless($this->mayManage($type, $actor), 403);

        return DB::transaction(function () use ($type, $id, $version, $actor): Response {
            app(PostWorkflowDefinitions::class)->lock($type);
            $board = ContentBoard::where('type', $type)->lockForUpdate()->findOrFail($id);
            abort_if($board->version !== $version, 409, __('kanban.stale'));
            $context = ['type' => $type, 'id' => $id, 'user_id' => (int) $actor->id, 'operation' => 'delete'];
            Hooks::lifecycle('jobic.posts.kanban.boards.deleting', $context);
            app(RecordHistory::class)->record('content_boards', $id, (int) $actor->id, 'delete', $board->toArray(), []);
            $board->delete();
            DB::afterCommit(static fn () => Hooks::notify('jobic.posts.kanban.boards.deleted', $context));
            app(\App\Support\KanbanRealtime::class)->changed($type);

            return Response::make(true, __('kanban.deleted'));
        });
    }

    /**
     * Read bounded board-setting history for an authorized manager.
     *
     * @param string $type
     * @param int $id
     * @param int $page
     * @param User $actor
     *
     * @return Response
     * @throws \Throwable
     */
    public function history(string $type, int $id, int $page, User $actor): Response
    {
        abort_unless($this->mayManage($type, $actor), 403);
        ContentBoard::where('type', $type)->findOrFail($id);

        return app(RecordHistory::class)->page('content_boards', $id, $page);
    }

    /**
     * Atomically delete explicitly selected, version-matched custom boards.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param User $actor
     *
     * @return Response
     * @throws \Throwable
     */
    public function bulk(string $type, array $data, User $actor): Response
    {
        $data = dto($data, ContentBoardBulkRequest::class);
        abort_unless($this->mayManage($type, $actor), 403);

        return DB::transaction(function () use ($type, $data, $actor): Response {
            app(PostWorkflowDefinitions::class)->lock($type);
            foreach ($data['items'] as $item) {
                $this->delete($type,(int) $item['id'],(int) $item['version'],$actor);
            }

            return Response::make(true,__('kanban.deleted'));
        });
    }
}
