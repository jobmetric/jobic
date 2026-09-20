<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use JobMetric\Metadata\Events\MetadataDeletedEvent;
use JobMetric\Metadata\Events\MetadataStoredEvent;

/**
 * Keep a user's database display name synchronized with profile metadata.
 */
class SynchronizeUserNameFromMetadata
{
    /**
     * Synchronize the user's full name after metadata is stored or deleted.
     *
     * @param MetadataStoredEvent|MetadataDeletedEvent $event
     *
     * @return void
     */
    public function handle(MetadataStoredEvent|MetadataDeletedEvent $event): void
    {
        $meta = $event->meta;
        if (! in_array($meta->key, ['first_name', 'last_name'], true)) {
            return;
        }

        $user = $event instanceof MetadataStoredEvent ? $event->model : $meta->metaable;
        if ($user instanceof User) {
            $user->synchronizeFullName();
        }
    }
}
