<?php

declare(strict_types=1);

namespace App\Extensions\Module\JobicMenu;

use App\Support\Layouts\SiteThemes;
use App\Support\MenuLinks\WebsiteMenus;
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
                    $field::select()->name('menu_id')->label('site.extensions.menu')
                        ->options(app(WebsiteMenus::class)->options())
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
        $depth = match ((string) ($options['depth'] ?? 'all')) {
            '1' => 1,
            '2' => 2,
            '3' => 3,
            default => null,
        };

        $menu = app(WebsiteMenus::class)->navigation((int) ($options['menu_id'] ?? 0), $depth);
        if ($menu === null) {
            return null;
        }

        $data = $menu + [
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
}
