<?php

declare(strict_types=1);

namespace App\Providers;

use App\CustomFields\SocialNetworks\SocialNetworks as SocialNetworksField;
use App\Services\Auth\Authentication;
use App\Services\Installation\Installation;
use App\Settings\SocialNetworksSetting;
use App\Support\Hooks;
use App\Support\Installation\Environment;
use App\Support\Installation\State;
use App\Support\SocialNetworks\SocialNetworkRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use JobMetric\Form\FormBuilder;
use JobMetric\Rolix\Events\RegisterPathPermissionEvent;
use JobMetric\Setting\Events\BuildingSettingForm;

/**
 * Register application services and presentation extensions.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton('authentication', fn () => new Authentication);
        $this->app->singleton(Environment::class, fn ($app) => new Environment($app->environmentFilePath(), $app->getCachedConfigPath()));
        $this->app->singleton(State::class, fn () => new State(storage_path('app/installation')));
        $this->app->singleton(Installation::class);
        $this->app->singleton(Hooks::class);
        $this->app->singleton(SocialNetworkRegistry::class, function (): SocialNetworkRegistry {
            return (new SocialNetworkRegistry)
                ->register('instagram', 'settings/social_networks.networks.instagram', 'brand-instagram', 'https://instagram.com/{slug}')
                ->register('telegram', 'settings/social_networks.networks.telegram', 'brand-telegram', 'https://t.me/{slug}')
                ->register('youtube', 'settings/social_networks.networks.youtube', 'brand-youtube', 'https://youtube.com/@{slug}')
                ->register('linkedin', 'settings/social_networks.networks.linkedin', 'brand-linkedin', 'https://linkedin.com/in/{slug}')
                ->register('x', 'settings/social_networks.networks.x', 'brand-x', 'https://x.com/{slug}')
                ->register('facebook', 'settings/social_networks.networks.facebook', 'brand-facebook', 'https://facebook.com/{slug}')
                ->register('github', 'settings/social_networks.networks.github', 'brand-github', 'https://github.com/{slug}');
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        app('CustomFieldRegistry')->register(new SocialNetworksField);
        app('SettingNamespaceRegistry')->register('App\\Settings');
        app('SettingRegistry')->register(SocialNetworksSetting::class);
        Blade::directive('cmsHook', fn ($expression) => "<?php echo app(\\App\\Support\\Hooks::class)->render($expression); ?>");
        Event::listen(BuildingSettingForm::class, function (BuildingSettingForm $event): void {
            $builder = app(Hooks::class)->filter('jobic.admin.settings.form.builder', $event->builder, [
                'application' => $event->setting->application(),
                'key' => $event->setting->key(),
                'form_name' => $event->setting->formName(),
                'user_id' => auth()->id(),
            ]);
            if (! $builder instanceof FormBuilder) {
                throw new \UnexpectedValueException('The dynamic settings form hook must return a FormBuilder.');
            }
            $names = [];
            foreach ($builder->build()->getAllCustomFields() as $field) {
                $name = $field->params['name'] ?? null;
                if (! is_string($name) || $name === '' || str_starts_with($name, '_') || in_array($name, $names, true)) {
                    throw new \UnexpectedValueException('Dynamic setting field names must be unique, non-empty, and not reserved.');
                }
                $names[] = $name;
            }
            $event->builder = $builder;
        });
        Event::listen(RegisterPathPermissionEvent::class, function (RegisterPathPermissionEvent $event): void {
            $event->addPath('dynamic-settings', app_path('Support/permissions/dynamic-settings.php'));
        });
    }
}
