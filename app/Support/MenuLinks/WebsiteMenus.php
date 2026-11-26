<?php

declare(strict_types=1);

namespace App\Support\MenuLinks;

use App\Support\TaxonomyPresentation;
use Illuminate\Support\Collection;
use JobMetric\Taxonomy\Models\Taxonomy;

/**
 * Prepare menu choices and public navigation trees from the menu inventory.
 */
final class WebsiteMenus
{
    /**
     * List menu choices with names in the current editor language.
     *
     * @return list<array{label: string, value: string}>
     */
    public function options(): array
    {
        return Taxonomy::query()->where('type', 'menu')->with('translations')
            ->orderBy('ordering')->orderBy('id')->get()
            ->map(fn (Taxonomy $menu): array => [
                'label' => TaxonomyPresentation::name($menu),
                'value' => (string) $menu->id,
            ])->all();
    }

    /**
     * Load an active menu and its visible branches in the saved order.
     *
     * @param int $menuId
     * @param int|null $depth
     *
     * @return array{name: string, items: list<array<string, mixed>>}|null
     */
    public function navigation(int $menuId, ?int $depth = null): ?array
    {
        if ($menuId < 1) {
            return null;
        }

        $menu = Taxonomy::query()->where('type', 'menu')->where('status', true)
            ->with('translations')->find($menuId);
        if ($menu === null) {
            return null;
        }

        $children = Taxonomy::query()->where('type', 'menu_'.$menu->id)->where('status', true)
            ->with(['translations', 'metas' => fn ($query) => $query->whereIn('key', ['menu_link_driver', 'menu_link_data'])])
            ->orderBy('ordering')->orderBy('id')->get()
            ->groupBy(fn (Taxonomy $item): int => (int) $item->parent_id);

        $items = $this->items($children, 0, $depth);

        return $items === [] ? null : ['name' => TaxonomyPresentation::name($menu), 'items' => $items];
    }

    /**
     * Build a tree of translated labels and safe public destinations.
     *
     * @param Collection<int, Collection<int, Taxonomy>> $children
     * @param int $parent
     * @param int|null $remainingLevels
     *
     * @return list<array<string, mixed>>
     */
    private function items(Collection $children, int $parent, ?int $remainingLevels): array
    {
        if ($remainingLevels === 0) {
            return [];
        }

        $nextLevel = $remainingLevels === null ? null : $remainingLevels - 1;

        return $children->get($parent, collect())->map(function (Taxonomy $item) use ($children, $nextLevel): array {
            $metadata = $item->metas->pluck('value', 'key');
            $driver = $metadata->get('menu_link_driver');
            $url = $driver === 'external'
                ? (json_decode((string) $metadata->get('menu_link_data'), true)['menu_link_url'] ?? null)
                : null;

            return [
                'label' => TaxonomyPresentation::name($item),
                'url' => is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
                    && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true) ? $url : null,
                'separator' => $driver === 'separator',
                'children' => $this->items($children, $item->id, $nextLevel),
            ];
        })->values()->all();
    }
}
