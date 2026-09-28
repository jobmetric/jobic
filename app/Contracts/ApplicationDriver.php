<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Contracts\Foundation\Application;

/**
 * Define an isolated application feature with registration and boot phases.
 */
interface ApplicationDriver
{
    /**
     * Register the driver's container bindings.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void;

    /**
     * Bootstrap the driver's listeners and framework integrations.
     *
     * @param Application $app
     *
     * @return void
     */
    public function boot(Application $app): void;
}
