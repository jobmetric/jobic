<?php

declare(strict_types=1);

namespace App\Http\Controllers\Taxonomies;

use App\Http\Controllers\Controller;
use App\Http\Requests\Taxonomies\TaxonomyRequest;
use App\Services\Taxonomies\Taxonomies;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;

/** Render the native, registry-driven taxonomy administration pages. */
class TaxonomyController extends Controller
{
    public function __construct(private readonly Taxonomies $taxonomies) {}

    public function index(string $type): View
    {
        $this->prepare($type);

        return view('taxonomies.index', ['records' => $this->taxonomies->records($type), 'type' => $type]);
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

    private function prepare(string $type): void
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);
        $builder = TaxonomyTypeRegistry::for($type);
        Page::prepare('taxonomies');
        Page::addLocalize('taxonomyPage', ['index' => route('admin.taxonomies.index', $type), 'type' => $type, 'label' => $builder->getLabel()]);
    }

    private function formData(string $type, mixed $record = null): array
    {
        $builder = TaxonomyTypeRegistry::for($type);

        return ['type' => $type, 'record' => $record, 'builder' => $builder, 'parents' => $builder->hasHierarchical() ? $this->taxonomies->records($type) : collect()];
    }
}
