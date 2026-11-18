<?php

return [
    'examples' => json_decode(file_get_contents(base_path('examples/extensions/catalog.json')), true, flags: JSON_THROW_ON_ERROR),
    'types' => [
        'Component' => ['label' => 'extensions.types.component', 'instances' => false, 'contains' => ['Module', 'Theme', 'Plugin']],
        'Module' => ['label' => 'extensions.types.module'],
        'Theme' => ['label' => 'extensions.types.theme'],
        'Plugin' => ['label' => 'extensions.types.plugin'],
    ],
    'runtime_path' => storage_path('app/extensions'),
    'root' => app_path('Extensions'),
    'php_binary' => env('EXTENSIONS_PHP_BINARY'),
    'safe_mode' => env('EXTENSIONS_SAFE_MODE', false),
    'zip_max_bytes' => 52428800,
    'extracted_max_bytes' => 104857600,
    'stage_ttl' => 1800,
    'max_files' => 2000,
];
