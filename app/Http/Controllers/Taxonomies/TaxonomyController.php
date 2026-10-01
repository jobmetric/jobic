<?php

declare(strict_types=1);

namespace App\Http\Controllers\Taxonomies;

use App\Facades\Hooks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Taxonomies\BulkRequest;
use App\Http\Requests\Taxonomies\TableRequest;
use App\Http\Requests\Taxonomies\TaxonomyRequest;
use App\Services\Taxonomies\Taxonomies;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;

/** Render the native, registry-driven taxonomy administration pages. */
class TaxonomyController extends Controller
{
    public function __construct(private readonly Taxonomies $taxonomies) {}

    /** @throws SetPluginNotFoundException */
    public function index(string $type): View
    {
        $this->prepare($type);
        Domi::setPlugins(['jquery', 'datatable']);

        return view('taxonomies.index', ['type' => $type]);
    }

    public function data(TableRequest $request, string $type): JsonResponse
    {
        return response()->json($this->taxonomies->table($type, $request->validated()));
    }

    public function create(string $type): View
    {
        $this->prepare($type);

        return view('taxonomies.form', $this->formData($type));
    }

    public function edit(string $type, int $taxonomy): View
    {
        $this->prepare($type);

        return view('taxonomies.form', $this->formData($type, $this->taxonomies->record($type, $taxonomy)));
    }

    public function store(TaxonomyRequest $request, string $type): JsonResponse
    {
        $result = $this->taxonomies->save($type, $request->validated());
        $result->data['edit_url'] = route('admin.taxonomies.edit', [$type, $result->data['id']]);

        return response()->json($result, $result->status);
    }

    public function update(TaxonomyRequest $request, string $type, int $taxonomy): JsonResponse
    {
        $result = $this->taxonomies->save($type, $request->validated(), $taxonomy);
        $result->data['edit_url'] = route('admin.taxonomies.edit', [$type, $taxonomy]);

        return response()->json($result, $result->status);
    }

    public function destroy(string $type, int $taxonomy): JsonResponse
    {
        $result = $this->taxonomies->delete($type, $taxonomy);

        return response()->json($result, $result->status);
    }

    public function bulk(BulkRequest $request, string $type): JsonResponse
    {
        $result = $this->taxonomies->bulk($type, $request->validated());

        return response()->json($result, $result->status);
    }

    private function prepare(string $type): void
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);
        $builder = TaxonomyTypeRegistry::for($type);
        Page::prepare('taxonomies');
        $context = ['type' => $type];
        Page::addLocalize('taxonomies', trans('taxonomies'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        $rowActions = Hooks::filter('jobic.taxonomies.list.actions', ['edit', 'delete'], $context);
        if ($type === 'menu') {
            $rowActions[] = [
                'key' => 'items',
                'label' => __('menus.configure'),
                'icon' => 'list-tree',
                'url' => url('/admin/taxonomies/menu_:id'),
            ];
        }
        Page::addLocalize('taxonomyPage', [
            'index' => route('admin.taxonomies.index', $type), 'data' => route('admin.taxonomies.data', $type),
            'bulk' => route('admin.taxonomies.bulk', $type), 'type' => $type, 'label' => $builder->getLabel(),
            'columns' => Hooks::filter('jobic.taxonomies.list.columns', ['name', 'ordering', 'status', 'created_at'], $context),
            'rowActions' => $rowActions,
            'toolbar' => Hooks::filter('jobic.taxonomies.list.toolbar', ['activate', 'deactivate', 'delete', 'help'], $context),
            'formToolbar' => Hooks::filter('jobic.taxonomies.form.toolbar', ['new', 'close'], $context),
            'help' => Hooks::filter('jobic.taxonomies.help.items', trans('taxonomies.help_items'), $context),
        ]);
    }

    private function formData(string $type, mixed $record = null): array
    {
        $builder = TaxonomyTypeRegistry::for($type);

        return ['type' => $type, 'record' => $record, 'builder' => $builder, 'parents' => $builder->hasHierarchical() ? $this->taxonomies->records($type) : collect()];
    }
}
