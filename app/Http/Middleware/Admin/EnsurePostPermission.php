<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Support\AdminAccess;
use App\Support\PostWorkflowPermissions;
use App\Services\Posts\PostStateAccess;
use Closure;
use Illuminate\Http\Request;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Facades\PostTypeRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Authorize post operations using the permissions configured for each post type. */
final readonly class EnsurePostPermission
{
    /**
     * Create the middleware with the shared administration access resolver.
     *
     * @param AdminAccess $access
     *
     * @return void
     */
    public function __construct(private AdminAccess $access)
    {
    }

    /**
     * Enforce the permission registered for the requested post type.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     *
     * @return Response
     * @throws HttpException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $type = (string) $request->route('type');

        abort_unless(PostTypeRegistry::has($type), 404);

        if ($request->routeIs('admin.posts.kanban.*')) {
            $builder = PostTypeRegistry::for($type);
            abort_unless($builder->hasKanban(), 404);
            $fallback = $type === 'page' ? 'admin.content.pages.view' : 'admin.content.posts.view';
            abort_unless($this->access->allows($request->user(), $builder->getViewPermission() ?? $fallback), 403);
            if ($request->isMethod('POST') && $request->input('operation') !== 'settings') {
                abort_unless($this->access->allows($request->user(), app(PostWorkflowPermissions::class)->for($type)['manage']), 403);
            }
            $this->authorizeRecord($request, $type);
            return $next($request);
        }

        $action = $request->route()
            ->getActionMethod();

        if ($request->routeIs('admin.posts.workflow.*', 'admin.posts.lifecycle.*')) {
            $keys = app(PostWorkflowPermissions::class)->for($type);

            $mayManage = $this->access->allows($request->user(), $keys['manage']);
            $mayView = $this->access->allows($request->user(), $keys['view']);

            abort_unless($request->routeIs('admin.posts.workflow.index') ? ($mayView || $mayManage) : $mayManage, 403);

            $this->authorizeRecord($request, $type);

            return $next($request);
        }

        if ($action === 'restoreRevision') {
            $permission = app(PostWorkflowPermissions::class)->for($type)['restore'];

            abort_unless($this->access->allows($request->user(), $permission), 403);

            $this->authorizeRecord($request, $type);

            return $next($request);
        }

        if (in_array($action, [
                'unlockEditLock',
                'unlockEditLocks',
            ], true) || ($action === 'bulk' && $request->input('operation') === 'unlock')) {

            $permission = (string) config('admin.permissions.unlock_edit_lock', 'admin.unlock_edit_lock');

            abort_unless($this->access->allows($request->user(), $permission), 403);

            $this->authorizeRecord($request, $type);

            return $next($request);
        }

        $read = $request->isMethod('GET') && in_array($action, [
                'index',
                'data',
                'show',
            ], true);
        $namespace = $type === 'page' ? 'admin.content.pages' : 'admin.content.posts';
        $fallback = $namespace . ($read ? '.view' : '.manage');
        $typeBuilder = PostTypeRegistry::for($type);
        $permission = $read ? $typeBuilder->getViewPermission() : $typeBuilder->getManagePermission();

        abort_unless($this->access->allows($request->user(), $permission ?? $fallback), 403);

        $this->authorizeRecord($request, $type);

        return $next($request);
    }

    /**
     * Enforce the current workflow state's allowed roles on record routes.
     *
     * @param Request $request
     * @param string $type
     *
     * @return void
     */
    private function authorizeRecord(Request $request, string $type): void
    {
        if ($request->routeIs('admin.posts.bulk', 'admin.posts.edit-locks.unlock')) {
            $ids = $request->input('ids');
            if (is_array($ids) && $ids !== [] && count($ids) <= 100 && collect($ids)->every(static fn ($value): bool => is_scalar($value) && ctype_digit((string) $value))) {
                $visible = app(PostStateAccess::class)->scopeVisible(Post::query()->where('type', $type)->whereIn('id', $ids), $type, $request->user())->count();
                abort_unless($visible === count(array_unique($ids)), 403);
            }
        }

        $id = $request->route('post');
        if ($id === null) {
            $id = $request->routeIs('admin.posts.workflow.index') ? $request->query('post') : null;
        }
        if (! is_scalar($id) || ! ctype_digit((string) $id)) {
            return;
        }

        $record = Post::query()->where('type', $type)->findOrFail((int) $id);
        abort_unless(app(PostStateAccess::class)->allows($record, $request->user()), 403);
    }
}
