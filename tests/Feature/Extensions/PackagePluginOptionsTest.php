<?php

namespace Tests\Feature\Extensions;

use Composer\InstalledVersions;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use JobMetric\Extension\Models\Extension;
use JobMetric\Extension\Models\Plugin;
use JobMetric\Extension\Services\Plugin as PluginService;
use Tests\Fixtures\Extensions\PaymentMethod\Beta\Beta;
use Tests\TestCase;

class PackagePluginOptionsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_package_runner_preserves_configured_array_object_fields(): void
    {
        if (! getenv('JOBIC_EXTENSION_PACKAGE_SOURCE') && version_compare((string) InstalledVersions::getVersion('jobmetric/laravel-extension'), '2.2.1', '<')) {
            $this->markTestSkipped('Requires the prepared laravel-extension 2.2.1 option cast fix.');
        }
        $extension = Extension::create(['extension' => 'PaymentMethod', 'name' => 'Beta', 'namespace' => Beta::class, 'info' => ['multiple' => true]]);
        $plugin = Plugin::create(['extension_id' => $extension->id, 'name' => 'Configured', 'status' => true, 'fields' => ['label' => 'Expected value']]);
        $this->assertSame('Expected value', app(PluginService::class)->run($plugin->id));
        $this->assertSame('Expected value', app(PluginService::class)->fields('PaymentMethod', 'Beta', $plugin->id)['values']['label']);
    }
}
