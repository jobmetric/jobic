<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\CustomFields\SocialNetworks\SocialNetworks as SocialNetworksField;
use App\Settings\SocialNetworksSetting;
use App\Support\SocialNetworks\SocialNetworkRegistry;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;

/**
 * Register the social-network catalog, field, and settings form.
 */
class SocialNetworksDriver implements ApplicationDriver
{
    /**
     * Register the extensible social-network catalog.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(SocialNetworkRegistry::class, function (): SocialNetworkRegistry {
            return (new SocialNetworkRegistry)->register('instagram', 'settings/social_networks.networks.instagram', 'brand-instagram', 'https://instagram.com/{slug}')
                ->register('telegram', 'settings/social_networks.networks.telegram', 'brand-telegram', 'https://t.me/{slug}')
                ->register('youtube', 'settings/social_networks.networks.youtube', 'brand-youtube', 'https://youtube.com/@{slug}')
                ->register('linkedin', 'settings/social_networks.networks.linkedin', 'brand-linkedin', 'https://linkedin.com/in/{slug}')
                ->register('x', 'settings/social_networks.networks.x', 'brand-x', 'https://x.com/{slug}')
                ->register('facebook', 'settings/social_networks.networks.facebook', 'brand-facebook', 'https://facebook.com/{slug}')
                ->register('github', 'settings/social_networks.networks.github', 'brand-github', 'https://github.com/{slug}');
        });
    }

    /**
     * Register the social settings field and form with their package registries.
     *
     * @param Application $app
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(Application $app): void
    {
        $app->make('CustomFieldRegistry')->register(new SocialNetworksField);
        $app->make('SettingRegistry')->register(SocialNetworksSetting::class);
    }
}
