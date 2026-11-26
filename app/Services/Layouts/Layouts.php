<?php

declare(strict_types=1);

namespace App\Services\Layouts;

use App\Facades\Hooks;
use App\Http\Requests\Layouts\LayoutRequest;
use App\Support\Layouts\PageTypeRegistry;
use App\Support\Layouts\SiteThemes;
use App\Support\Layouts\ThemeManifest;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\Extension\Models\Plugin;
use JobMetric\Layout\Facades\Layout as LayoutService;
use JobMetric\Layout\Models\Layout;
use JobMetric\Layout\Models\LayoutPage;
use JobMetric\PackageCore\Output\Response;

/**
 * Manage Theme-owned named layouts and validated Module placements.
 */
class Layouts
{
    /**
     * Create the layout service.
     *
     * @param SiteThemes $themes
     * @param ThemeManifest $manifest
     * @param PageTypeRegistry $pageTypes
     *
     * @return void
     */
    public function __construct(
        private readonly SiteThemes $themes,
        private readonly ThemeManifest $manifest,
        private readonly PageTypeRegistry $pageTypes
    ) {
    }

    /**
     * Return layouts with their page assignments and placements.
     *
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        return Layout::withTrashed()->with(['layoutPages', 'layoutPlugins.plugin.extension'])->latest('id')->get()->map(function (Layout $layout): array {
            return [
                'id' => $layout->id, 'name' => $layout->name, 'status' => $layout->status,
                'deleted' => $layout->trashed(), 'theme' => $layout->layoutPages->first()?->application,
                'page_types' => $layout->layoutPages->pluck('page')->filter()->values()->all(),
                'plugins' => $layout->layoutPlugins->count(), 'version' => $this->version($layout),
            ];
        })->all();
    }

    /**
     * Return active Theme labels keyed by their stable identifiers.
     *
     * @return array<string, string>
     */
    public function themes(): array
    {
        return collect($this->themes->available())->mapWithKeys(fn ($theme, string $key): array => [
            $key => trans($theme->info['title'] ?? $theme->name),
        ])->all();
    }

    /**
     * Return a filtered and paginated layout table response.
     *
     * @param array<string, mixed> $input
     *
     * @return Response
     */
    public function table(array $input): Response
    {
        $rows = $this->all();
        $themes = $this->themes();
        foreach ($rows as &$row) {
            $row['theme_label'] = $themes[$row['theme']] ?? $row['theme'];
            $row['page_types_label'] = collect($row['page_types'])->map(fn (string $type): string => trans('layouts.page_types.'.$type))->implode(', ');
            $row['state'] = $row['deleted'] ? 'deleted' : ($row['status'] ? 'active' : 'inactive');
        }
        unset($row);
        $stats = [count($rows), count(array_filter($rows, fn (array $row): bool => $row['state'] === 'active')),
            count(array_filter($rows, fn (array $row): bool => $row['state'] === 'inactive')),
            count(array_filter($rows, fn (array $row): bool => $row['state'] === 'deleted'))];
        $quickStats = ['all' => count($rows)];
        foreach (['active', 'inactive', 'deleted'] as $state) {
            $quickStats[$state] = count(array_filter($rows, fn (array $row): bool => $row['state'] === $state));
        }
        $search = mb_strtolower((string) ($input['search']['value'] ?? ''));
        $filtered = array_values(array_filter($rows, fn (array $row): bool =>
            ($search === '' || str_contains(mb_strtolower($row['name'].' '.$row['theme_label'].' '.$row['page_types_label']), $search))
            && (empty($input['theme']) || $row['theme'] === $input['theme'])
            && (empty($input['status']) || $row['state'] === $input['status'])));
        $column = [1 => 'name', 2 => 'theme_label', 3 => 'page_types_label', 4 => 'plugins', 5 => 'state'][(int) ($input['order'][0]['column'] ?? 1)];
        usort($filtered, fn (array $left, array $right): int => (($left[$column] <=> $right[$column]) ?: ($left['id'] <=> $right['id']))
            * (($input['order'][0]['dir'] ?? 'asc') === 'desc' ? -1 : 1));

        return Response::make(true, '', [
            'draw' => (int) $input['draw'], 'recordsTotal' => count($rows), 'recordsFiltered' => count($filtered),
            'stats' => $stats, 'quick_stats' => $quickStats,
            'data' => array_slice($filtered, (int) $input['start'], (int) $input['length']),
        ]);
    }

    /**
     * Apply one lifecycle operation to a validated layout selection.
     *
     * @param array<int, array{id: int, _version: string}> $entries
     * @param string $operation
     * @param int $userId
     *
     * @return Response
     * @throws ValidationException
     */
    public function bulk(array $entries, string $operation, int $userId): Response
    {
        DB::transaction(function () use ($entries, $operation, $userId): void {
            $layouts = [];
            foreach (collect($entries)->sortBy('id') as $entry) {
                $layout = Layout::withTrashed()->lockForUpdate()->findOrFail($entry['id']);
                if (! hash_equals($this->version($layout), $entry['_version'])) {
                    abort(409, trans('layouts.conflict'));
                }
                if ($operation === 'force_delete' && ! $layout->trashed()) {
                    throw ValidationException::withMessages(['entries' => trans('layouts.force_delete_requires_trash')]);
                }
                $layouts[] = $layout;
            }
            foreach ($layouts as $layout) {
                if ($operation === 'force_delete') {
                    $context = ['operation' => $operation, 'layout_id' => $layout->id, 'user_id' => $userId];
                    Hooks::lifecycle('jobic.settings.layouts.saving', $context);
                    LayoutService::forceDelete($layout->id);
                    abort_if(Layout::withTrashed()->whereKey($layout->id)->exists(), 409, trans('layouts.conflict'));
                    DB::afterCommit(fn () => Hooks::notify('jobic.settings.layouts.saved', $context));
                    continue;
                }
                if ($operation === 'restore') {
                    $this->restore($layout->id, $userId);
                    continue;
                }
                if ($layout->trashed()) {
                    throw ValidationException::withMessages(['entries' => trans('layouts.invalid_bulk_selection')]);
                }
                if ($operation === 'delete') {
                    $this->delete($layout, $userId);
                    continue;
                }
                $status = $operation === 'activate';
                Hooks::lifecycle('jobic.settings.layouts.saving', ['operation' => $operation, 'layout_id' => $layout->id, 'user_id' => $userId]);
                $layout->update(['status' => $status]);
                $context = ['operation' => $operation, 'layout_id' => $layout->id, 'user_id' => $userId];
                DB::afterCommit(fn () => Hooks::notify('jobic.settings.layouts.saved', $context));
            }
        });

        return Response::make(true, $operation === 'force_delete'
            ? trans_choice('layouts.force_deleted', count($entries))
            : trans('layouts.bulk_saved'));
    }

    /**
     * Return editor data for a new or existing layout.
     *
     * @param Layout|null $layout
     *
     * @return array<string, mixed>
     */
    public function editor(?Layout $layout = null): array
    {
        $themes = $this->themes->available();
        $themeKey = $layout?->layoutPages->first()?->application ?: array_key_first($themes);
        $theme = $themeKey !== null ? ($themes[$themeKey] ?? null) : null;

        return [
            'layout' => $layout?->load(['layoutPages', 'layoutPlugins']),
            'themes' => $themes,
            'themeKey' => $themeKey,
            'positions' => $theme ? $this->manifest->positions($theme->namespace) : [],
            'themePositions' => collect($themes)->mapWithKeys(fn ($item, string $key): array => [$key => $this->manifest->positions($item->namespace)])->all(),
            'pageTypes' => $this->pageTypes->translated(),
            'modules' => Plugin::query()->with('extension')->whereHas('extension', fn ($query) => $query->where('extension', 'Module'))->get(),
            'version' => $layout ? $this->version($layout) : null,
        ];
    }

    /**
     * Store or update one validated layout.
     *
     * @param array<string, mixed> $data
     * @param Layout|null $layout
     * @param int $userId
     *
     * @return Response
     * @throws ValidationException
     */
    public function save(array $data, ?Layout $layout, int $userId): Response
    {
        $data = dto($data, LayoutRequest::class);
        $theme = $this->themes->available()[$data['theme']] ?? null;
        if ($theme === null) {
            throw ValidationException::withMessages(['theme' => trans('layouts.invalid_theme')]);
        }
        $positions = array_keys($this->manifest->positions($theme->namespace));
        foreach ($data['page_types'] as $pageType) {
            if (! $this->pageTypes->has($pageType)) {
                throw ValidationException::withMessages(['page_types' => trans('layouts.invalid_page_type')]);
            }
        }
        foreach ($data['plugins'] as $placement) {
            $plugin = Plugin::with('extension')->find($placement['plugin_id']);
            if ($plugin?->extension?->extension !== 'Module' || ! in_array($placement['position'], $positions, true)) {
                throw ValidationException::withMessages(['plugins' => trans('layouts.invalid_placement')]);
            }
        }
        $context = ['operation' => $layout ? 'update' : 'create', 'layout_id' => $layout?->id,
            'theme' => $data['theme'], 'page_types' => $data['page_types'], 'positions' => array_values(array_unique(array_column($data['plugins'], 'position'))), 'user_id' => $userId];
        $saved = DB::transaction(function () use ($data, $layout, $context): Layout {
            if ($layout !== null) {
                $layout = Layout::query()->lockForUpdate()->findOrFail($layout->id);
                if (! hash_equals($this->version($layout), (string) ($data['_version'] ?? ''))) {
                    abort(409, trans('layouts.conflict'));
                }
            }
            foreach ($data['page_types'] as $pageType) {
                $conflict = LayoutPage::query()->where('application', $data['theme'])->where('page', $pageType)
                    ->whereHas('layout', fn ($query) => $query->whereNull('deleted_at'));
                if ($layout) {
                    $conflict->where('layout_id', '!=', $layout->id);
                }
                if ($conflict->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['page_types' => trans('layouts.page_type_conflict')]);
                }
            }
            Hooks::lifecycle('jobic.settings.layouts.saving', $context);
            $layout ??= new Layout;
            $layout->fill(['name' => $data['name'], 'status' => (bool) $data['status']])->save();
            $layout->layoutPages()->delete();
            $layout->layoutPages()->createMany(array_map(fn (string $page): array => ['application' => $data['theme'], 'page' => $page], $data['page_types']));
            $layout->layoutPlugins()->delete();
            if ($data['plugins'] !== []) {
                try {
                    $layout->layoutPlugins()->createMany($data['plugins']);
                } catch (UniqueConstraintViolationException $exception) {
                    if (! str_contains($exception->getMessage(), 'LAYOUT_PLUGIN_UNIQUE_KEY')) {
                        throw $exception;
                    }

                    throw ValidationException::withMessages([
                        'plugins' => trans('layouts.duplicate_placement'),
                    ]);
                }
            }

            $context['layout_id'] = $layout->id;
            DB::afterCommit(fn () => Hooks::notify('jobic.settings.layouts.saved', $context));

            return $layout;
        });

        return Response::make(true, trans('layouts.saved'), [
            'id' => $saved->id, 'version' => $this->version($saved),
            'form_action' => route('admin.settings.layouts.update', $saved), 'form_method' => 'PUT',
        ]);
    }

    /**
     * Soft-delete one layout.
     *
     * @param Layout $layout
     * @param int $userId
     *
     * @return Response
     */
    public function delete(Layout $layout, int $userId): Response
    {
        Hooks::lifecycle('jobic.settings.layouts.saving', ['operation' => 'delete', 'layout_id' => $layout->id, 'user_id' => $userId]);
        $layout->delete();
        $context = ['operation' => 'delete', 'layout_id' => $layout->id, 'user_id' => $userId];
        DB::afterCommit(fn () => Hooks::notify('jobic.settings.layouts.saved', $context));

        return Response::make(true, trans('layouts.deleted'));
    }

    /**
     * Restore one soft-deleted layout.
     *
     * @param int $id
     * @param int $userId
     *
     * @return Response
     * @throws ValidationException
     */
    public function restore(int $id, int $userId): Response
    {
        $layout = Layout::withTrashed()->findOrFail($id);
        foreach ($layout->layoutPages as $page) {
            if (LayoutPage::query()->where('application', $page->application)->where('page', $page->page)
                ->where('layout_id', '!=', $layout->id)->whereHas('layout', fn ($query) => $query->whereNull('deleted_at'))->exists()) {
                throw ValidationException::withMessages(['layout' => trans('layouts.page_type_conflict')]);
            }
        }
        Hooks::lifecycle('jobic.settings.layouts.saving', ['operation' => 'restore', 'layout_id' => $layout->id, 'user_id' => $userId]);
        $layout->restore();
        $context = ['operation' => 'restore', 'layout_id' => $layout->id, 'user_id' => $userId];
        DB::afterCommit(fn () => Hooks::notify('jobic.settings.layouts.saved', $context));

        return Response::make(true, trans('layouts.restored'));
    }

    /**
     * Return an opaque optimistic version for a layout record.
     *
     * @param Layout $layout
     *
     * @return string
     */
    private function version(Layout $layout): string
    {
        return hash('sha256', implode('|', [$layout->id, $layout->updated_at?->format('U.u'), $layout->deleted_at?->format('U.u')]));
    }
}
