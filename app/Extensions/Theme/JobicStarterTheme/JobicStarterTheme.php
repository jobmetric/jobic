<?php
declare(strict_types=1);

namespace App\Extensions\Theme\JobicStarterTheme;

use App\Contracts\ThemeHeaderNavigation;
use App\Support\MenuLinks\WebsiteMenus;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;
use JobMetric\Taxonomy\Models\Taxonomy;

/** Register a bundled website theme extension. */
final class JobicStarterTheme extends AbstractExtension implements ThemeHeaderNavigation
{
    /**
     * Configure the bundled extension.
     *
     * @param ExtensionCore $extension
     *
     * @return void
     */
    public function configuration(ExtensionCore $extension): void {}

    /**
     * Let administrators select the main menu for the website header.
     *
     * @return FormBuilder
     */
    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('navigation')->label('site.main_menu')
                ->customField(function (CustomFieldBuilder $field): void {
                    $field::select()->name('main_menu_id')->label('site.main_menu')
                        ->options(app(WebsiteMenus::class)->options())
                        ->validation(['nullable', 'integer', Rule::exists(Taxonomy::class, 'id')->where('type', 'menu')]);
                });
        });
    }

    /**
     * Render the main navigation when the selected menu has active items.
     *
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    public function headerNavigation(array $options): ?string
    {
        $menu = app(WebsiteMenus::class)->navigation((int) ($options['main_menu_id'] ?? 0));

        return $menu === null ? null : View::file(__DIR__.'/resources/views/header-menu.blade.php', $menu)->render();
    }

    /**
     * Render the starter theme content.
     *
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    public function handle(array $options = []): ?string
    {
        return null;
    }
}
