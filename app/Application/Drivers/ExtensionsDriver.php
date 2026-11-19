<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\Hooks;
use App\Support\AdminAccess;
use App\Support\Extensions\InstalledFile;
use App\Support\Extensions\Plugins;
use App\Support\Extensions\Runtime;
use Illuminate\Contracts\Foundation\Application;
use JobMetric\Extension\Facades\ExtensionNamespaceRegistry;
use JobMetric\Extension\Facades\ExtensionTypeRegistry;
use JobMetric\Extension\Kernel\ExtensionKernel;

/**
 * Connect the package lifecycle to Jobic activation and settings policies.
 */
class ExtensionsDriver implements ApplicationDriver
{
    /**
     * Decorate public package services with CMS activation policy.
     *
     * @param  Application  $app
     *
     * @return void
     * @throws \Throwable
     */
    public function register(Application $app): void
    {
        foreach (config('extensions.types', []) as $type => $options) {
            ExtensionTypeRegistry::register($type, $options);
        }
        $app->singleton('InstalledExtensionsFile', InstalledFile::class);
        $app->bind('Plugin', Plugins::class);
        $app->make('ExtensionKernel')->discovering(static function (): void {
            if (app(Runtime::class)->recovering()) {
                foreach (ExtensionNamespaceRegistry::all() as $namespace) {
                    ExtensionNamespaceRegistry::unregister($namespace);
                }
                ExtensionKernel::clearDiscoverCache();
            }
        });
    }

    /**
     * Expose the authorized extension manager in the settings registry.
     *
     * @param  Application  $app
     *
     * @return void
     */
    public function boot(Application $app): void
    {
        Hooks::addFilter('jobic.admin.settings.sections', static function (array $sections): array {
            if (app(AdminAccess::class)->allows(auth()->user(), 'admin.settings.extensions.view')) {
                $sections['extensions'] = [
                    'label' => trans('extensions.title'), 'description' => trans('extensions.intro'),
                    'icon' => 'puzzle', 'url' => route('admin.settings.extensions.index'), 'order' => 70,
                ];
            }

            return $sections;
        });
    }
}
