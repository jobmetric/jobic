<?php

declare(strict_types=1);

namespace App\Listeners\Permissions;

use App\Services\Settings\DynamicSettings;
use App\Support\Permissions\PermissionRegistry;
use App\Support\TaxonomyPermissions;
use JobMetric\Rolix\Events\RegisterPathPermissionEvent;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;

/**
 * Synchronize discovered settings permissions and expose the catalog to Rolix.
 */
readonly class RegisterCmsPermissions
{
    /**
     * Create the Rolix permission registration listener.
     *
     * @param PermissionRegistry $permissions
     * @param DynamicSettings $settings
     *
     * @return void
     */
    public function __construct(
        private PermissionRegistry $permissions,
        private DynamicSettings $settings
    ) {
    }

    /**
     * Add discovered setting permissions before registering the Rolix adapter file.
     *
     * @param RegisterPathPermissionEvent $event
     *
     * @return void
     */
    public function handle(RegisterPathPermissionEvent $event): void
    {
        foreach ($this->settings->descriptors() as $setting) {
            $this->permissions->addPermission($setting['permission'], $setting['title'], 'admin.settings.forms.edit', [
                'application' => $setting['application'],
                'key'         => $setting['key'],
            ], 100);
        }

        $taxonomyPermissions = app(TaxonomyPermissions::class);
        foreach (TaxonomyTypeRegistry::values() as $type) {
            $taxonomyPermissions->for($type, TaxonomyTypeRegistry::for($type));
        }

        $event->addPath('cms', app_path('Support/Permissions/registry.php'));
    }
}
