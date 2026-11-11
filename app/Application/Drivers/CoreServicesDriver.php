<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Services\Auth\Authentication;
use App\Services\Installation\Installation;
use App\Support\Hooks;
use App\Support\Installation\Environment;
use App\Support\Installation\State;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;

/**
 * Register application-wide services and shared Blade extensions.
 */
class CoreServicesDriver implements ApplicationDriver
{
    /**
     * Register core application services.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton('authentication', fn () => new Authentication);
        $app->singleton(Environment::class, fn (Application $container
        ) => new Environment($container->environmentFilePath(), $container->getCachedConfigPath()));
        $app->singleton(State::class, fn () => new State(storage_path('app/installation')));
        $app->singleton(Installation::class);
        $app->singleton(Hooks::class);
        $app->singleton(\App\Support\KanbanRealtime::class);
        $app->singleton(\App\Support\KanbanFilterRegistry::class, function () {
            $registry = new \App\Support\KanbanFilterRegistry;
            $registry->register('author', new \App\Kanban\Filters\AuthorFilter);
            $registry->register('taxonomy', new \App\Kanban\Filters\TaxonomyFilter);
            return $registry;
        });
    }

    /**
     * Register shared application presentation directives.
     *
     * @param Application $app
     *
     * @return void
     */
    public function boot(Application $app): void
    {
        Blade::directive('cmsHook', fn ($expression
        ) => "<?php echo app(\\App\\Support\\Hooks::class)->render($expression); ?>");
    }
}
