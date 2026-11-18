<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use JobMetric\Extension\Facades\ExtensionNamespaceRegistry;
use Tests\TestCase;

/**
 * Bootstrap extension tests without discovering or executing deployment-installed extension files.
 */
abstract class ExtensionManagementTestCase extends TestCase
{
    /**
     * Use an empty private installation snapshot before package discovery and provider boot.
     *
     * @return \Illuminate\Foundation\Application
     * @throws \Throwable
     */
    public function createApplication()
    {
        $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
        $this->traitsUsedByTest = class_uses_recursive(static::class);
        $app->afterBootstrapping(RegisterProviders::class, function (): void {
            ExtensionNamespaceRegistry::unregister('App\\Extensions');
            config(['extensions.installed_file' => storage_path('framework/testing/empty-extension-snapshot-'.bin2hex(random_bytes(8)).'.json')]);
        });
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
