<?php

namespace Tests\Feature\Installation;

use Tests\TestCase;

class HttpMigrationDiscoveryTest extends TestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__, 3).'/bootstrap/app.php';
        (new \ReflectionProperty($app, 'isRunningInConsole'))->setValue($app, false);
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    public function test_package_migrations_register_in_http_runtime(): void
    {
        $this->assertFalse($this->app->runningInConsole());
        $paths = app('migrator')->paths();
        foreach (['laravel-language', 'laravel-translation', 'laravel-flow'] as $package) {
            $this->assertTrue(collect($paths)->contains(fn ($path) => str_contains(str_replace('\\', '/', $path), $package.'/src/../database/migrations')), $package);
        }
    }
}
