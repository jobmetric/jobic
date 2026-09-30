<?php

declare(strict_types=1);

namespace App\Http\Controllers\Menus;

use App\Http\Controllers\Controller;
use App\Http\Requests\Menus\BulkRequest;
use App\Http\Requests\Menus\DeleteMenuRequest;
use App\Http\Requests\Menus\MenuRequest;
use App\Http\Requests\Menus\TableRequest;
use App\Services\Menus\Menus;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use Throwable;

/**
 * Present menu pages and map validated mutations to the menu service.
 */
class MenuController extends Controller
{
    /**
     * Resolve the menu aggregate service.
     *
     *
     * @return void
     */
    public function __construct(private readonly Menus $menus) {}

    /**
     * Render the menu container list.
     *
     * @throws SetPluginNotFoundException
     */
    public function index(): View
    {
        $this->prepare();
        Domi::setPlugins(['jquery', 'datatable']);

        return view('menus.index');
    }

    /**
     * Return a bounded server-side page of menus.
     *
     *
     * @throws Throwable
     */
    public function data(TableRequest $request): JsonResponse
    {
        return response()->json($this->menus->table($request->validated()));
    }

    /**
     * Render a blank menu form.
     *
     * @throws Throwable
     */
    public function create(): View
    {
        $this->prepare();

        return view('menus.form', ['record' => null, 'readonly' => false]);
    }

    /**
     * Render an existing menu for editing.
     *
     *
     * @throws Throwable
     */
    public function edit(int $menu): View
    {
        $this->prepare();

        return view('menus.form', ['record' => $this->menus->record($menu), 'readonly' => false]);
    }

    /**
     * Render an existing menu without editable controls.
     *
     *
     * @throws Throwable
     */
    public function show(int $menu): View
    {
        $this->prepare();

        return view('menus.form', ['record' => $this->menus->record($menu), 'readonly' => true]);
    }

    /**
     * Create a menu from validated input.
     *
     *
     * @throws Throwable
     */
    public function store(MenuRequest $request): JsonResponse
    {
        $result = $this->menus->saveRecord($request->validated(), null, (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Update a menu from validated input.
     *
     *
     * @throws Throwable
     */
    public function update(MenuRequest $request, int $menu): JsonResponse
    {
        $result = $this->menus->saveRecord($request->validated(), $menu, (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Delete a menu and its owned items.
     *
     *
     * @throws Throwable
     */
    public function destroy(DeleteMenuRequest $request, int $menu): JsonResponse
    {
        $result = $this->menus->delete($menu, (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Apply an atomic operation to selected menus.
     *
     *
     * @throws Throwable
     */
    public function bulk(BulkRequest $request): JsonResponse
    {
        $result = $this->menus->bulk($request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Publish menu labels and routes through the page presentation registry.
     *
     * @throws Throwable
     */
    private function prepare(): void
    {
        Page::prepare('menus');
        Page::addLocalize('menus', trans('menus'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        Page::addLocalize('menuRoutes', [
            'index' => route('admin.menus.index'),
            'data' => route('admin.menus.data'),
            'bulk' => route('admin.menus.bulk'),
            'taxonomies' => url('/admin/taxonomies'),
        ]);
        Page::addLocalize('menuToolbar', [
            'list' => ['activate', 'deactivate', 'delete', 'help'],
            'form' => ['new', 'close'],
        ]);
    }
}
