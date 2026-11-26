<?php

declare(strict_types=1);

namespace App\Extensions\Module\JobicMenu;

use App\Support\Layouts\SiteThemes;
use App\Support\TaxonomyPresentation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
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
     * Let each plugin instance select a menu and configure its presentation.
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
                        ->options($options)
                        ->validation(['required', 'integer', Rule::exists(Taxonomy::class, 'id')->where('type', 'menu')]);
                });

            $views = app(SiteThemes::class)->menuViews();
            if ($views !== []) {
                $tab->customField(function (CustomFieldBuilder $field) use ($views): void {
                    $options = [['label' => __('site.menu_display.standard'), 'value' => '']];
                    foreach ($views as $key => $view) {
                        $options[] = ['label' => $view['label'], 'value' => $key];
                    }

                    $field::select()->name('menu_view')->label('site.menu_display.label')->options($options)
                        ->validation(['nullable', Rule::in(array_keys($views))]);
                });
            }

            foreach ([
                'orientation' => ['horizontal', 'vertical'],
                'alignment' => ['start', 'center', 'end'],
                'show_title' => ['hide', 'show'],
                'depth' => ['all', '1', '2', '3'],
            ] as $name => $values) {
                $tab->customField(function (CustomFieldBuilder $field) use ($name, $values): void {
                    $field::select()->name($name)->label('site.menu_settings.'.$name)->value($values[0])
                        ->options(array_map(fn (string $value): array => [
                            'label' => __('site.menu_settings.'.$name.'_options.'.$value),
                            'value' => $value,
                        ], $values))
                        ->validation(['nullable', Rule::in($values)]);
                });
            }
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

        $depth = match ((string) ($options['depth'] ?? 'all')) {
            '1' => 1,
            '2' => 2,
            '3' => 3,
            default => null,
        };

        $data = [
            'name' => TaxonomyPresentation::name($menu),
            'items' => $this->items($children, 0, $depth),
            'vertical' => ($options['orientation'] ?? null) === 'vertical',
            'alignment' => match ($options['alignment'] ?? null) {
                'center' => 'center',
                'end' => 'end',
                default => 'start',
            },
            'showTitle' => ($options['show_title'] ?? null) === 'show',
        ];
        $display = (string) ($options['menu_view'] ?? '');
        $path = $display === '' ? null : (app(SiteThemes::class)->menuViews()[$display]['path'] ?? null);

        return ($path === null ? view('site.modules.menu', $data) : View::file($path, $data))->render();
    }

    /**
     * Build a tree of labels and safe public destinations for the menu view.
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
