<?php

declare(strict_types=1);

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\BulkRequest;
use App\Http\Requests\Roles\HistoryRequest;
use App\Http\Requests\Roles\RoleRequest;
use App\Http\Requests\Roles\TableRequest;
use App\Services\Roles\Roles;
use App\Support\AdminAccess;
use App\Support\Page;
use App\Support\Permissions\PermissionTreePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Rolix\Facades\Permission;
use JobMetric\Rolix\Facades\RuleEvaluatorRegistry;
use Throwable;

/**
 * Present system-role pages and map validated requests to the role service.
 */
class RoleController extends Controller
{
    /**
     * Resolve the role aggregate service.
     *
     * @param Roles $roles
     *
     * @return void
     */
    public function __construct(private readonly Roles $roles)
    {
    }

    /**
     * Render the empty server-side role table shell.
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function index(): View
    {
        $this->prepare();
        Domi::setPlugins(['jquery', 'datatable']);

        return view('roles.index');
    }

    /**
     * Return a validated server-side role table page.
     *
     * @param TableRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function data(TableRequest $request): JsonResponse
    {
        return response()->json($this->roles->table($request->validated()));
    }

    /**
     * Render a blank system-role form.
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function create(): View
    {
        $permissionTree = $this->prepareForm();

        return view('roles.form', [
            'record'         => null,
            'readonly'       => false,
            'parents'        => $this->roles->parentOptions(),
            'permissionTree' => $permissionTree,
        ]);
    }

    /**
     * Render an editable system role.
     *
     * @param int $role
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function edit(int $role): View
    {
        $permissionTree = $this->prepareForm();

        return view('roles.form', [
            'record'         => $this->roles->record($role),
            'readonly'       => false,
            'parents'        => $this->roles->parentOptions($role),
            'permissionTree' => $permissionTree,
        ]);
    }

    /**
     * Render a read-only system role.
     *
     * @param int $role
     *
     * @return View
     * @throws SetPluginNotFoundException
     */
    public function show(int $role): View
    {
        $permissionTree = $this->prepareForm();

        return view('roles.form', [
            'record'         => $this->roles->record($role),
            'readonly'       => true,
            'parents'        => $this->roles->parentOptions($role),
            'permissionTree' => $permissionTree,
        ]);
    }

    /**
     * Create a system role from validated form values.
     *
     * @param RoleRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(RoleRequest $request): JsonResponse
    {
        return $this->mutation($this->roles->saveRecord($request->validated(), null, (int) $request->user()->id));
    }

    /**
     * Persist edits to an existing system role.
     *
     * @param RoleRequest $request
     * @param int $role
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(RoleRequest $request, int $role): JsonResponse
    {
        return $this->mutation($this->roles->saveRecord($request->validated(), $role, (int) $request->user()->id));
    }

    /**
     * Delete a validated role selection.
     *
     * @param BulkRequest $request
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function bulk(BulkRequest $request): JsonResponse
    {
        return $this->mutation($this->roles->bulk($request->validated(), (int) $request->user()->id));
    }

    /**
     * Return validated history pagination for an authenticated reader.
     *
     * @param HistoryRequest $request
     * @param int $role
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function history(HistoryRequest $request, int $role): JsonResponse
    {
        return response()->json($this->roles->history($role, $request->validated()));
    }

    /**
     * Add mutation status and the renewed CSRF token to a JSON response.
     *
     * @param Response $result
     *
     * @return JsonResponse
     */
    private function mutation(Response $result): JsonResponse
    {
        return response()
            ->json($result, $result->status)
            ->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Publish list translations, routes, and toolbar metadata through Domi.
     *
     * @return void
     * @throws SetPluginNotFoundException
     */
    private function prepare(): void
    {
        Page::prepare('roles');
        Page::addLocalize('roles', trans('roles'));
        Page::addLocalize('roleFields', trans('rolix::base.fields'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        Page::addLocalize('roleRoutes', [
            'index' => route('admin.roles.index'),
            'data'  => route('admin.roles.data'),
            'bulk'  => route('admin.roles.bulk'),
        ]);
        Page::addLocalize('roleToolbar', [
            'list' => [
                'delete',
                'help',
            ],
            'form' => [
                'new',
                'close',
            ],
        ]);
        Page::addLocalize('roleAccess', [
            'manage' => app(AdminAccess::class)->allows(auth()->user(), 'admin.roles.manage'),
        ]);
    }

    /**
     * Publish the permission tree and registered rule-driver forms for role editing.
     *
     * @return array<int, array<string, mixed>>
     * @throws SetPluginNotFoundException
     */
    private function prepareForm(): array
    {
        $this->prepare();

        $drivers = [];
        foreach (RuleEvaluatorRegistry::values() as $name) {
            $drivers[$name] = $this->localizeDriverForm(RuleEvaluatorRegistry::formFor($name));
            $drivers[$name]['label'] = trans('rolix::base.rule_evaluators.' . $name . '.label');
            $drivers[$name]['description'] = trans('rolix::base.rule_evaluators.' . $name . '.description');
        }

        $permissionTree = app(PermissionTreePresenter::class)->present(Permission::getPermissionTreeForType('system'));

        Page::addLocalize('roleForm', [
            'permissions' => $permissionTree,
            'drivers'     => $drivers,
        ]);

        return $permissionTree;
    }

    /**
     * Translate public labels and help text in a Rolix driver form schema.
     *
     * @param array<string, mixed>|null $schema
     *
     * @return array<string, mixed>|null
     */
    private function localizeDriverForm(?array $schema): ?array
    {
        if ($schema === null) {
            return null;
        }

        $walk = function (array $node) use (&$walk): array {
            foreach ($node as $key => $value) {
                if (is_array($value)) {
                    $node[$key] = $walk($value);
                } else {
                    if (in_array($key, [
                            'label',
                            'info',
                        ], true) && is_string($value) && str_contains($value, '::')) {
                        $node[$key] = trans($value);
                    }
                }
            }

            return $node;
        };

        return $walk($schema);
    }
}
