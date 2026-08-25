<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web installer
    |--------------------------------------------------------------------------
    |
    | The installer is opt-in while it is under development. Set
    | INSTALLATION_ENABLED=true only when the installation flow is ready.
    | INSTALLATION may later be used as the installation completion marker.
    |
    */
    'enabled' => (bool) env('INSTALLATION_ENABLED', false),

    'completed' => filled(env('INSTALLATION')),
];
