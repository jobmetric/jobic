<?php

declare(strict_types=1);

namespace App\Http\Controllers\Layouts;

use App\Http\Controllers\Controller;
use App\Http\Requests\Layouts\LayoutRequest;
use App\Http\Requests\Layouts\BulkRequest;
use App\Http\Requests\Layouts\TableRequest;
use App\Services\Layouts\Layouts;
use App\Support\AdminAccess;
use App\Support\Page;
use JobMetric\Domi\Facades\Domi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JobMetric\Layout\Models\Layout;

/**
 * Serve the settings UI and mutations for named website layouts.
 */
class LayoutController extends Controller
{
    /**
     * Create the controller with its domain service.
     *
     * @param Layouts $layouts
     *
     * @return void
     */
    public function __construct(private readonly Layouts $layouts)
    {
    }

    /**
     * Render the layout inventory.
     *
     * @return View
     */
    public function index(): View
    {
        $this->prepare();
        Domi::setPlugins(['jquery', 'datatable']);

        return view('layouts.index', ['section' => 'layouts', 'themes' => $this->layouts->themes()]);
    }

    /**
     * Return a filtered server-side page of layouts.
     *
     * @param TableRequest $request
     *
     * @return JsonResponse
     */
    public function data(TableRequest $request): JsonResponse
    {
        return response()->json($this->layouts->table($request->validated()));
    }

    /**
     * Activate selected layouts.
     *
     * @param BulkRequest $request
     *
     * @return JsonResponse
     */
    public function bulkActivate(BulkRequest $request): JsonResponse
    {
        return response()->json($this->layouts->bulk($request->validated()['entries'], 'activate', (int) $request->user()->id));
    }

    /**
     * Deactivate selected layouts.
     *
     * @param BulkRequest $request
     *
     * @return JsonResponse
     */
    public function bulkDeactivate(BulkRequest $request): JsonResponse
    {
        return response()->json($this->layouts->bulk($request->validated()['entries'], 'deactivate', (int) $request->user()->id));
    }

    /**
     * Soft-delete selected layouts.
     *
     * @param BulkRequest $request
     *
     * @return JsonResponse
     */
    public function bulkDelete(BulkRequest $request): JsonResponse
    {
        return response()->json($this->layouts->bulk($request->validated()['entries'], 'delete', (int) $request->user()->id));
    }

    /**
     * Restore selected layouts.
     *
     * @param BulkRequest $request
     *
     * @return JsonResponse
     */
    public function bulkRestore(BulkRequest $request): JsonResponse
    {
        return response()->json($this->layouts->bulk($request->validated()['entries'], 'restore', (int) $request->user()->id));
    }

    /**
     * Render the create form.
     *
     * @return View
     */
    public function create(): View
    {
        $this->prepare('layouts-form');

        return view('layouts.form', ['section' => 'layouts'] + $this->layouts->editor());
    }

    /**
     * Render the edit form.
     *
     * @param Layout $layout
     *
     * @return View
     */
    public function edit(Layout $layout): View
    {
        $this->prepare('layouts-form');

        return view('layouts.form', ['section' => 'layouts'] + $this->layouts->editor($layout));
    }

    /**
     * Store a new named layout.
     *
     * @param LayoutRequest $request
     *
     * @return JsonResponse|RedirectResponse
     */
    public function store(LayoutRequest $request): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->layouts->save($request->validated(), null, (int) $request->user()->id));
    }

    /**
     * Update an existing named layout.
     *
     * @param LayoutRequest $request
     * @param Layout $layout
     *
     * @return JsonResponse|RedirectResponse
     */
    public function update(LayoutRequest $request, Layout $layout): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->layouts->save($request->validated(), $layout, (int) $request->user()->id));
    }

    /**
     * Soft-delete an existing layout.
     *
     * @param Request $request
     * @param Layout $layout
     *
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(Request $request, Layout $layout): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->layouts->delete($layout, (int) $request->user()->id));
    }

    /**
     * Restore a soft-deleted layout.
     *
     * @param Request $request
     * @param int $layout
     *
     * @return JsonResponse|RedirectResponse
     */
    public function restore(Request $request, int $layout): JsonResponse|RedirectResponse
    {
        return $this->respond($request, $this->layouts->restore($layout, (int) $request->user()->id));
    }

    /**
     * Convert a PackageCore response to the shared transport contract.
     *
     * @param Request $request
     * @param object $result
     *
     * @return JsonResponse|RedirectResponse
     */
    private function respond(Request $request, object $result): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
        }

        return redirect()->route('admin.settings.layouts.index')->with('status', $result->message);
    }

    /**
     * Publish translated copy, routes and access decisions through Domi.
     *
     * @param string $page
     *
     * @return void
     */
    private function prepare(string $page = 'layouts'): void
    {
        Page::prepare($page);
        Page::addLocalize('layouts', trans('layouts'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        Page::addLocalize('layoutRoutes', [
            'index' => route('admin.settings.layouts.index'),
            'data' => route('admin.settings.layouts.data'),
            'create' => route('admin.settings.layouts.create'),
            'extensions' => route('admin.settings.extensions.index'),
            'bulk' => [
                'activate' => route('admin.settings.layouts.bulk.activate'),
                'deactivate' => route('admin.settings.layouts.bulk.deactivate'),
                'delete' => route('admin.settings.layouts.bulk.delete'),
                'restore' => route('admin.settings.layouts.bulk.restore'),
            ],
        ]);
        Page::addLocalize('layoutAccess', [
            'manage' => app(AdminAccess::class)->allows(auth()->user(), 'admin.settings.layouts.manage'),
            'delete' => app(AdminAccess::class)->allows(auth()->user(), 'admin.settings.layouts.delete'),
            'restore' => app(AdminAccess::class)->allows(auth()->user(), 'admin.settings.layouts.restore'),
            'extensions_view' => app(AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.view'),
            'has_themes' => $this->layouts->themes() !== [],
        ]);
    }
}
