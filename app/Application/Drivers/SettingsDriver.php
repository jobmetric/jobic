<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use JobMetric\Setting\Support\SettingNamespaceRegistry;

/**
 * Connect database-backed setting forms to CMS extension points.
 */
class SettingsDriver implements ApplicationDriver
{
    /**
     * Register settings driver bindings.
     *
     * @param Application $app
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register(Application $app): void
    {
        $app->instance(SettingNamespaceRegistry::class, $app->make('SettingNamespaceRegistry'));
    }

    /**
     * Register the application settings namespace.
     *
     * @param Application $app
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(Application $app): void
    {
        $app->make('SettingNamespaceRegistry')->register('App\\Settings');
    }
}
