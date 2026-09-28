<?php

use App\Application\Drivers\CoreServicesDriver;
use App\Application\Drivers\PermissionsDriver;
use App\Application\Drivers\SettingsDriver;
use App\Application\Drivers\SocialNetworksDriver;

return [
    CoreServicesDriver::class,
    SettingsDriver::class,
    SocialNetworksDriver::class,
    PermissionsDriver::class,
];
