<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\SynchronizeUserNameFromMetadata;
use Illuminate\Support\ServiceProvider;
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
     */
    public function boot(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->app['events']->listen($event, $listener);
            }
        }
    }
}
