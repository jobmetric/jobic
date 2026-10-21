<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\SynchronizeUserNameFromMetadata;
use App\Support\LifecycleEvents;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\ServiceProvider;
use JobMetric\EventSystem\Support\EventRegistry;
use JobMetric\Metadata\Events\MetadataDeletedEvent;
use JobMetric\Metadata\Events\MetadataStoredEvent;

/**
 * Register application event listeners.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * Map domain events to their application listeners.
     *
     * @var array<class-string, list<class-string>>
     */
    protected array $listen = [
        MetadataStoredEvent::class  => [SynchronizeUserNameFromMetadata::class],
        MetadataDeletedEvent::class => [SynchronizeUserNameFromMetadata::class],
    ];

    /**
     * Register the event listeners with Laravel's dispatcher.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        /** @var EventRegistry $registry */
        $registry = $this->app->make('EventRegistry');
        foreach (
            [
                \App\Events\User\UserStoreEvent::class,
                \App\Events\User\UserUpdateEvent::class,
                \App\Events\User\UserDeleteEvent::class,
                \App\Events\User\UserActivateEvent::class,
                \App\Events\User\UserDeactivateEvent::class,
                \App\Events\Role\RoleStoreEvent::class,
                \App\Events\Role\RoleUpdateEvent::class,
                \App\Events\Role\RoleDeleteEvent::class,
                \App\Events\Language\LanguageStoreEvent::class,
                \App\Events\Language\LanguageUpdateEvent::class,
                \App\Events\Language\LanguageDeleteEvent::class,
                \App\Events\Language\LanguageActivateEvent::class,
                \App\Events\Language\LanguageDeactivateEvent::class,
                \App\Events\General\GeneralUpdateEvent::class,
                \App\Events\Mail\MailUpdateEvent::class,
                \App\Events\Cache\CacheUpdateEvent::class,
                \App\Events\Appearance\AppearanceUpdateEvent::class,
                \App\Events\Profile\ProfileUpdateEvent::class,
                \App\Events\Security\SecurityUpdateEvent::class,
                \App\Events\Auth\AuthLoginEvent::class,
                \App\Events\Auth\AuthLogoutEvent::class,
                \App\Events\Auth\AuthResetLinkEvent::class,
                \App\Events\Auth\AuthPasswordResetEvent::class,
                \App\Events\Auth\AuthLanguageEvent::class,
                \App\Events\TwoFactor\TwoFactorSetupEvent::class,
                \App\Events\TwoFactor\TwoFactorEnableEvent::class,
                \App\Events\TwoFactor\TwoFactorDisableEvent::class,
                \App\Events\EditorDocument\EditorDocumentStoreEvent::class,
                \App\Events\EditorDocument\EditorDocumentUpdateEvent::class,
                \App\Events\EditorDocument\EditorDocumentDeleteEvent::class,
            ] as $event) {
            $registry->register($event);
        }

        foreach (LifecycleEvents::CLASSES as $event) {
            $registry->register($event);
        }

        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->app['events']->listen($event, $listener);
            }
        }
    }
}
