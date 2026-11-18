<?php

declare(strict_types=1);

namespace App\Http\Controllers\Extensions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Extensions\BulkRequest;
use App\Http\Requests\Extensions\ExtensionBulkRequest;
use App\Http\Requests\Extensions\CommandRequest;
use App\Http\Requests\Extensions\ExampleRequest;
use App\Http\Requests\Extensions\DiscardArchiveRequest;
use App\Http\Requests\Extensions\HistoryRequest;
use App\Http\Requests\Extensions\PluginRequest;
use App\Http\Requests\Extensions\TableRequest;
use App\Services\Extensions\Extensions;
use App\Support\AdminAccess;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\View;
use JobMetric\Domi\Facades\Domi;
use JobMetric\Extension\Facades\ExtensionTypeRegistry;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Present extension management and map validated commands to the domain service.
 */
class ExtensionController extends Controller
{
    /**
     * Resolve the extension aggregate adapter.
     *
     * @param  Extensions  $extensions
     *
     * @return void
     */
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * Render the shared empty table shell.
     *
     * @return View
     * @throws \Throwable
     */
    public function index(): View
    {
        $this->prepare();
        Domi::setPlugins(['jquery', 'datatable', 'select2']);

        return view('extensions.index', ['types' => ExtensionTypeRegistry::all()]);
    }

    /**
     * Return a bounded server-side table page.
     *
     * @param  TableRequest  $request
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function data(TableRequest $request): JsonResponse
    {
        return response()->json($this->extensions->table($request->validated()));
    }

    /**
     * Render an archive upload or upgrade form with an optimistic token.
     *
     * @param  CommandRequest  $request
     *
     * @return View
     * @throws \Throwable
     */
    public function create(CommandRequest $request): View
    {
        $this->prepare();
        Page::addStyle(Vite::asset('resources/css/extensions-upload.css', 'vuero/build'));

        return view('extensions.upload', $this->extensions->archivePage((string) ($request->validated()['namespace'] ?? ''), $request->routeIs('admin.settings.extensions.upgrade-form')));
    }

    /**
     * Execute the operation selected by the permission-protected route.
     *
     * @param  CommandRequest  $request
     * @param  string  $operation
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function command(CommandRequest $request, string $operation): JsonResponse
    {
        $result = $this->extensions->command($operation, $request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Execute a route-authorized extension selection through the lifecycle service.
     *
     * @param ExtensionBulkRequest $request
     * @param string $operation
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function bulkExtensions(ExtensionBulkRequest $request, string $operation): JsonResponse
    {
        $result = $this->extensions->bulkExtensions($operation, $request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Download one named example for manual upload and lifecycle testing.
     *
     * @param  ExampleRequest  $request
     *
     * @return BinaryFileResponse
     * @throws \Throwable
     */
    public function exampleArchive(ExampleRequest $request): BinaryFileResponse
    {
        $result = $this->extensions->exampleArchive($request->validated());

        return response()->download($result->data['path']);
    }

    /**
     * Place the reviewed archive only after the actor explicitly confirms its ticket.
     *
     * @param  CommandRequest  $request
     * @param  string  $operation
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function confirmArchive(CommandRequest $request, string $operation): JsonResponse
    {
        $result = $this->extensions->confirmArchive($operation, $request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Remove the authenticated actor's abandoned upload or upgrade review.
     *
     * @param DiscardArchiveRequest $request
     * @param string $operation
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function discardArchive(DiscardArchiveRequest $request, string $operation): JsonResponse
    {
        $result = $this->extensions->discardArchive($operation, $request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Render an empty server-side instance table in the shared CRUD shell.
     *
     * @param  int  $extension
     *
     * @return View
     * @throws \Throwable
     */
    public function pluginIndex(int $extension): View
    {
        $page = $this->extensions->pluginPage($extension);
        $this->prepare($extension, $page['canCreate']);
        Domi::setPlugins(['jquery', 'datatable', 'select2']);

        return view('extensions.plugin-index', ['extension' => $page['extension'], 'back_url' => $page['back_url'], 'extension_active' => $page['extension_active']]);
    }

    /**
     * Return the validated instance table page.
     *
     * @param  TableRequest  $request
     * @param  int  $extension
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function pluginData(TableRequest $request, int $extension): JsonResponse
    {
        return response()->json($this->extensions->pluginTable($extension, $request->validated()));
    }

    /**
     * Apply an explicitly confirmed instance selection.
     *
     * @param  BulkRequest  $request
     * @param  int  $extension
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function bulkPlugins(BulkRequest $request, int $extension): JsonResponse
    {
        $result = $this->extensions->bulkPlugins($extension, $request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Apply a permission-protected instance command.
     *
     * @param  CommandRequest  $request
     * @param  int  $extension
     * @param  int  $plugin
     * @param  string  $operation
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function pluginCommand(CommandRequest $request, int $extension, int $plugin, string $operation): JsonResponse
    {
        $result = $this->extensions->pluginCommand($extension, $plugin, $operation, $request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Return bounded public instance history.
     *
     * @param  HistoryRequest  $request
     * @param  int  $extension
     * @param  int  $plugin
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function pluginHistory(HistoryRequest $request, int $extension, int $plugin): JsonResponse
    {
        return response()->json($this->extensions->pluginHistory($extension, $plugin, (int) ($request->validated()['page'] ?? 1)));
    }

    /**
     * Render package-defined plugin fields and the extension's instances.
     *
     * @param  int  $extension
     * @param  int|null  $plugin
     *
     * @return View
     * @throws \Throwable
     */
    public function plugins(int $extension, ?int $plugin = null): View
    {
        $page = $this->extensions->pluginPage($extension, $plugin);
        $this->prepare($extension, (bool) ($page['extension']->info['multiple'] ?? false));
        abort_unless($plugin || $page['canCreate'], 409, trans('extensions.single_instance'));

        return view('extensions.plugins', $page);
    }

    /**
     * Save validated plugin fields through the package service.
     *
     * @param  PluginRequest  $request
     * @param  int  $extension
     * @param  int|null  $plugin
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function savePlugin(PluginRequest $request, int $extension, ?int $plugin = null): JsonResponse
    {
        $result = $this->extensions->savePlugin($extension, $plugin, $request->validated(), (int) $request->user()->id);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Delete an explicitly confirmed plugin belonging to this extension.
     *
     * @param  CommandRequest  $request
     * @param  int  $extension
     * @param  int  $plugin
     *
     * @return JsonResponse
     * @throws \Throwable
     */
    public function deletePlugin(CommandRequest $request, int $extension, int $plugin): JsonResponse
    {
        $result = $this->extensions->savePlugin($extension, $plugin, $request->validated(), (int) $request->user()->id, true);

        return response()->json($result, $result->status)->header('X-CSRF-TOKEN', csrf_token());
    }

    /**
     * Register translated copy, public routes and access decisions through Domi.
     *
     * @param  int|null  $extensionId
     * @param  bool  $canCreate
     *
     * @return void
     * @throws \Throwable
     */
    private function prepare(?int $extensionId = null, bool $canCreate = false): void
    {
        Page::prepare('extensions');
        Page::addLocalize('extensions', trans('extensions'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        $routes = ['index' => route('admin.settings.extensions.index'), 'data' => route('admin.settings.extensions.data'),
            'create' => route('admin.settings.extensions.create'), 'upgradeForm' => route('admin.settings.extensions.upgrade-form')];
        foreach (['upload', 'install', 'activate', 'deactivate', 'upgrade', 'uninstall', 'delete'] as $operation) {
            $routes[$operation] = route('admin.settings.extensions.'.$operation);
        }
        $routes['bulkOperations'] = [];
        foreach (['activate', 'deactivate', 'delete'] as $operation) {
            $routes['bulkOperations'][$operation] = route('admin.settings.extensions.bulk.'.$operation);
        }
        if ($extensionId !== null) {
            $routes = ['index' => route('admin.settings.extensions.plugins', $extensionId),
                'data' => route('admin.settings.extensions.plugins.data', $extensionId),
                'bulk' => route('admin.settings.extensions.plugins.bulk', $extensionId),
                'create' => route('admin.settings.extensions.plugins.create', $extensionId)];
            Page::addLocalize('pluginContext', ['enabled' => true, 'canCreate' => $canCreate, 'backUrl' => route('admin.settings.extensions.index')]);
        }
        Page::addLocalize('extensionRoutes', $routes);
        $access = [];
        foreach (['view', 'upload', 'install', 'activate', 'deactivate', 'upgrade', 'uninstall', 'delete', 'plugins'] as $operation) {
            $access[$operation] = app(AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.'.$operation);
        }
        Page::addLocalize('extensionAccess', $access);
    }
}
