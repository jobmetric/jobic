<?php

declare(strict_types=1);

namespace App\Http\Controllers\Taxonomies;

use App\Facades\Hooks;
use App\Http\Controllers\Controller;
use App\Http\Requests\Taxonomies\BulkRequest;
use App\Http\Requests\Taxonomies\HistoryRequest;
use App\Http\Requests\Taxonomies\TableRequest;
use App\Http\Requests\Taxonomies\TaxonomyRequest;
use App\Services\Taxonomies\Taxonomies;
use App\Support\LocaleCatalog;
use App\Support\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
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

    public function translation(Request $request, string $type, int $taxonomy, string $locale): JsonResponse
    {
        abort_unless(array_key_exists($locale, LocaleCatalog::all()), 404);
        $record = $this->taxonomies->record($type, $taxonomy);
        $builder = TaxonomyTypeRegistry::for($type);
        $rules = [];
        foreach ($builder->getTranslation() as $item) {
            $field = (string) Arr::get($item->customField->params, 'uniqName');
            if ($field !== '') {
                $rules[$field] = $field === 'name' ? ['required', 'string', 'max:255'] : ['nullable', 'string'];
            }
        }
        $values = Validator::make((array) $request->input('fields'), $rules)->validate();
        $result = $this->taxonomies->saveTranslation($type, $record, $locale, $values);

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

    /**
     * Return one bounded page of changes for a taxonomy record.
     */
    public function history(HistoryRequest $request, string $type, int $taxonomy): JsonResponse
    {
        return response()->json($this->taxonomies->history($type, $taxonomy, $request->validated()));
    }

    private function prepare(string $type): void
    {
        abort_unless(TaxonomyTypeRegistry::has($type), 404);
        $builder = TaxonomyTypeRegistry::for($type);
        Page::prepare('taxonomies');
        $context = ['type' => $type];
        Page::addLocalize('taxonomies', trans('taxonomies'));
        Page::addLocalize('crudHelp', trans('crud.help_sections'));
        $translationFields = $builder->getTranslation()->map(function ($item): array {
            $field = (string) Arr::get($item->customField->params, 'uniqName');
            $translated = trans((string) ($item->customField->label ?: $field));
            if ($translated === $item->customField->label) {
                $translated = trans('taxonomies.'.$field);
            }

            return ['name' => $field, 'label' => $translated, 'type' => $field !== 'name' && in_array($item->customField->type, ['textarea', 'editor'], true) ? 'textarea' : 'text'];
        })->filter(fn (array $field): bool => $field['name'] !== '')->values()->all();
        $columns = ['name', 'ordering', 'status', 'translations'];
        if ($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty()) {
            array_unshift($columns, 'media');
        }
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
            'columns' => Hooks::filter('jobic.taxonomies.list.columns', $columns, $context),
            'languages' => collect(LocaleCatalog::all())->map(fn (array $language): array => $language + ['flag_url' => LocaleCatalog::flagUrl($language['flag'] ?? null)])->all(),
            'activeLocale' => LocaleCatalog::resolve(app()->getLocale()),
            'hierarchical' => $builder->hasHierarchical(),
            'backUrl' => TaxonomyTypeRegistry::getOption($type, 'back-url'),
            'translationFields' => $translationFields,
            'translationUrl' => route('admin.taxonomies.translation', [$type, '__ID__', '__LOCALE__']),
            'rowActions' => $rowActions,
            'toolbar' => Hooks::filter('jobic.taxonomies.list.toolbar', ['activate', 'deactivate', 'delete', 'help'], $context),
            'formToolbar' => Hooks::filter('jobic.taxonomies.form.toolbar', ['new', 'close'], $context),
            'help' => Hooks::filter('jobic.taxonomies.help.items', trans($type === 'menu' ? 'menus.help_items' : 'taxonomies.help_items'), $context),
            'helpIntro' => trans($type === 'menu' ? 'menus.help_intro' : 'taxonomies.help_intro'),
        ]);
    }

    private function formData(string $type, mixed $record = null): array
    {
        $builder = TaxonomyTypeRegistry::for($type);

        return ['type' => $type, 'record' => $record, 'builder' => $builder,
            'parents' => $builder->hasHierarchical() ? $this->taxonomies->records($type) : collect(),
            'languages' => collect(LocaleCatalog::all())->map(fn (array $language): array => $language + ['flag_url' => LocaleCatalog::flagUrl($language['flag'] ?? null)])->all(), 'activeLocale' => LocaleCatalog::resolve(app()->getLocale()),
            'translations' => $record?->getTranslations() ?? [],
            'mediaPreview' => $record && ($builder->hasBaseMedia() || $builder->getMedia()->isNotEmpty()) ? $this->taxonomies->mediaPreview($record) : null];
    }
}
