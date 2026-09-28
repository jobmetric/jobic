<?php

$permissions = [];

$descriptors = app(\App\Services\Settings\DynamicSettings::class)->descriptors();
foreach ($descriptors as $setting) {
    $permissions[$setting['permission']] = $setting['title'];
}

return $permissions;
