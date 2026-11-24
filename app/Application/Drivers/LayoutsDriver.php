<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\Hooks;
use App\Support\AdminAccess;
use App\Support\Layouts\PageTypeRegistry;
use Illuminate\Contracts\Foundation\Application;

/**
 * Register layout discovery, built-in page types, and settings navigation.
 */
class LayoutsDriver implements ApplicationDriver
{
    /**
     * Register singleton layout registries.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(PageTypeRegistry::class);
    }

    /**
     * Register built-in page types and the authorized settings entry.
     *
     * @param Application $app
     *
     * @return void
     */
    public function boot(Application $app): void
    {
        $types = $app->make(PageTypeRegistry::class);
        foreach (['home', 'post', 'page', 'taxonomy', 'tag', 'brand'] as $type) {
            $types->register($type, 'layouts.page_types.'.$type);
        }
        Hooks::addFilter('jobic.admin.settings.sections', static function (array $sections): array {
            if (app(AdminAccess::class)->allows(auth()->user(), 'admin.settings.layouts.view')) {
                $sections['layouts'] = [
                    'label' => trans('layouts.title'), 'description' => trans('layouts.intro'),
                    'icon' => 'layout', 'url' => route('admin.settings.layouts.index'), 'order' => 65,
                ];
            }

            return $sections;
        });
    }
}
