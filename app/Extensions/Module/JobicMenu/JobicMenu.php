<?php

declare(strict_types=1);

namespace App\Extensions\Module\JobicMenu;

use App\Support\TaxonomyPresentation;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;
use JobMetric\Taxonomy\Models\Taxonomy;

/**
 * Display an independently selected menu in a public layout position.
 */
final class JobicMenu extends AbstractExtension
{
    /**
     * Register the module with the extension runtime.
     *
     * @param ExtensionCore $extension
     *
     * @return void
     */
    public function configuration(ExtensionCore $extension): void {}

    /**
     * Let each plugin instance select a menu from the content registry.
     *
     * @return FormBuilder
     */
    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('menu')->label('site.extensions.menu')
                ->customField(function (CustomFieldBuilder $field): void {
                    $options = Taxonomy::query()->where('type', 'menu')->with('translations')
                        ->orderBy('ordering')->orderBy('id')->get()
                        ->map(fn (Taxonomy $menu): array => [
                            'label' => TaxonomyPresentation::name($menu),
                            'value' => (string) $menu->id,
                        ])->all();

                    $field::select()->name('menu_id')->label('site.extensions.menu')
                        ->options([['label' => __('site.select_menu'), 'value' => ''], ...$options])
                        ->validation(['required', 'integer', Rule::exists(Taxonomy::class, 'id')->where('type', 'menu')]);
                });
        });
    }

    /**
     * Render the selected menu and its active branches in their saved order.
     *
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    public function handle(array $options = []): ?string
    {
        $menu = Taxonomy::query()->where('type', 'menu')->where('status', true)
            ->with('translations')->find((int) ($options['menu_id'] ?? 0));
        if ($menu === null) {
            return null;
        }

        $children = Taxonomy::query()->where('type', 'menu_'.$menu->id)->where('status', true)
            ->with(['translations', 'metas' => fn ($query) => $query->whereIn('key', ['menu_link_driver', 'menu_link_data'])])
            ->orderBy('ordering')->orderBy('id')->get()
            ->groupBy(fn (Taxonomy $item): int => (int) $item->parent_id);

        if (! $children->has(0)) {
            return null;
        }

        return view('site.modules.menu', [
            'name' => TaxonomyPresentation::name($menu),
            'items' => $this->items($children, 0),
        ])->render();
    }

    /**
     * Build a tree of labels and safe public destinations for the menu view.
     *
     * @param Collection<int, Collection<int, Taxonomy>> $children
     * @param int $parent
     *
     * @return list<array<string, mixed>>
     */
    private function items(Collection $children, int $parent): array
    {
        return $children->get($parent, collect())->map(function (Taxonomy $item) use ($children): array {
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
                'children' => $this->items($children, $item->id),
            ];
        })->values()->all();
    }
}
