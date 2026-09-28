<?php

declare(strict_types=1);

namespace App\Support\Application;

use App\Contracts\ApplicationDriver;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;

/**
 * Coordinate application feature drivers through Laravel's lifecycle.
 */
class ApplicationDriverManager
{
    /** @var list<ApplicationDriver> */
    private array $drivers = [];

    /**
     * Create the manager for the configured application drivers.
     *
     * @param Application $app
     * @param list<class-string<ApplicationDriver>> $driverClasses
     *
     * @return void
     */
    public function __construct(private readonly Application $app, private readonly array $driverClasses)
    {
    }

    /**
     * Resolve every driver and register its bindings in declaration order.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        foreach ($this->driverClasses as $driverClass) {
            $driver = $this->app->make($driverClass);
            if (! $driver instanceof ApplicationDriver) {
                throw new InvalidArgumentException(sprintf('%s must implement %s.', $driverClass, ApplicationDriver::class));
            }

            $driver->register($this->app);
            $this->drivers[] = $driver;
        }
    }

    /**
     * Bootstrap every previously registered driver in declaration order.
     *
     * @return void
     */
    public function boot(): void
    {
        foreach ($this->drivers as $driver) {
            $driver->boot($this->app);
        }
    }
}
