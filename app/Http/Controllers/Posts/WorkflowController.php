<?php

declare(strict_types=1);

namespace App\Http\Controllers\Posts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Posts\WorkflowRequest;
use App\Services\Posts\PostLifecycleView;
use App\Services\Posts\WorkflowDesigner;
use App\Support\AdminAccess;
use App\Support\LocaleCatalog;
use App\Support\Page;
use App\Support\PostWorkflowPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Rolix\Models\Role;
use Throwable;

/** Present and persist the post-type workflow designer through its domain adapter. */
class WorkflowController extends Controller
{
    /**
     * Render the full-screen designer without the administration shell.
     *
     * @param Request $request
     * @param string $type
     * @param WorkflowDesigner $designer
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function index(Request $request, string $type, WorkflowDesigner $designer): View
    {
        Page::prepare('posts-workflow');

        $permission = app(PostWorkflowPermissions::class)->for($type);

        $post = filter_var($request->query('post'), FILTER_VALIDATE_INT);

        $record = $post ? Post::query()
            ->where('type', $type)
            ->findOrFail($post) : null;

        $previewOnly = $record !== null && $request->query('preview') === '1';
        $initial = $designer->read($type);
        $systemRoles = Role::query()
            ->where('type', 'system')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Role $role): array => ['id' => (int) $role->id, 'name' => $role->name])
            ->all();
        if ($previewOnly) {
            $definition = app(PostLifecycleView::class)->activeDefinition($type);
            abort_unless($definition, 404);
            $initial['graph'] = $definition->graph;
            $initial['drivers'] = [];
        }

        $currentStatus = $record ? PostPublicationStream::query()
            ->where('post_id', $record->id)
            ->where('locale', PostPublicationStream::SHARED)
            ->value('status') : null;

        Page::addLocalize('workflow', [
            'initial'           => $initial,
            'viewRoles'         => $systemRoles,
            'copy'              => array_merge(trans('workflow_canvas'), trans('workflow'), array_intersect_key(trans('kanban'), array_flip(['transition_roles', 'transition_roles_hint', 'legacy_roles', 'select_role', 'remove_role']))),
            'previewOnly'       => $previewOnly,
            'editable'          => ! $previewOnly && app(AdminAccess::class)->allows($request->user(), $permission['manage']),
            'url'               => route('admin.posts.workflow.save', ['type' => $type]),
            'backUrl'           => $record ? route('admin.posts.edit', [
                'type' => $type,
                'post' => $record->id,
            ]) : route('admin.posts.index', ['type' => $type]),
            'currentStatus'     => $currentStatus,
            'typeLabel'         => __(PostTypeRegistry::getOption($type, 'label', $type)),
            'statusChoices'     => app(\App\Services\Posts\PostWorkflow::class)->constrain($type, collect([
                'editable',
                'publish',
                'future',
                'archive',
                'disabled',
            ])
                ->mapWithKeys(static fn (string $status): array => [$status => __('posts.statuses.' . $status)])
                ->all()),
            'simulationRoles'   => $systemRoles,
            'simulationLocales' => collect(LocaleCatalog::all())
                ->map(fn (array $locale, string $code): array => [
                    'code' => $code,
                    'name' => $locale['name'] ?? strtoupper($code),
                ])
                ->values()
                ->all(),
            'appearance'        => array_replace(config('admin.appearance'), $request->user()?->ui_preferences ?? []),
        ]);

        return view('posts.workflow', compact('type'));
    }

    /**
     * Submit a graph command without replacing the mounted document.
     *
     * @param WorkflowRequest $request
     * @param string $type
     * @param WorkflowDesigner $designer
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function save(WorkflowRequest $request, string $type, WorkflowDesigner $designer): JsonResponse
    {
        $result = $designer->save($type, $request->validated(), $request->user());

        return response()->json($result);
    }
}
