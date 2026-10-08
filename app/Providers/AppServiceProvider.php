<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Application\ApplicationDriverManager;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use JobMetric\CustomField\CustomFieldBuilder;

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
        $this->registerCustomFieldMacros();

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

    /**
     * Register custom-field macros before application drivers register taxonomy types.
     *
     * @return void
     */
    private function registerCustomFieldMacros(): void
    {
        if (! $this->app->bound('CustomFieldRegistry')) {
            return;
        }

        foreach ($this->app->make('CustomFieldRegistry')->all() as $customField) {
            $macro = Str::camel($customField::type());

            if (! CustomFieldBuilder::hasMacro($macro)) {
                $customField->init($customField);
            }
        }
    }
}
