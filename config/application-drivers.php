<?php

use App\Application\Drivers\AdminMenuDriver;
use App\Application\Drivers\CoreServicesDriver;
use App\Application\Drivers\EditorDriver;
use App\Application\Drivers\MenuLinksDriver;
use App\Application\Drivers\PermissionsDriver;
use App\Application\Drivers\PostTypeDriver;
use App\Application\Drivers\SettingsDriver;
use App\Application\Drivers\SocialNetworksDriver;
use App\Application\Drivers\TaxonomyDriver;

return [
    CoreServicesDriver::class,
    EditorDriver::class,
    SettingsDriver::class,
    SocialNetworksDriver::class,
    TaxonomyDriver::class,
    PostTypeDriver::class,
    MenuLinksDriver::class,
    PermissionsDriver::class,
    AdminMenuDriver::class,
];
