<?php

namespace App\Providers;

use App\Services\Auth\Authentication;
use App\Services\Installation\Installation;
use App\Support\Installation\Environment;
use App\Support\Installation\State;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('authentication', fn () => new Authentication);
        $this->app->singleton(Environment::class, fn ($app) => new Environment($app->environmentFilePath(), $app->getCachedConfigPath()));
        $this->app->singleton(State::class, fn () => new State(storage_path('app/installation')));
        $this->app->singleton(Installation::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
