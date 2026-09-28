<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Application\ApplicationDriverManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;

/**
 * Register application services and presentation extensions.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $manager = new ApplicationDriverManager($this->app, config('application-drivers', []));
        $this->app->instance(ApplicationDriverManager::class, $manager);
        $manager->register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        $this->app->make(ApplicationDriverManager::class)->boot();
    }
}
