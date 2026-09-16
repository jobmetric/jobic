<?php

declare(strict_types=1);

// Domi 1.14 uses the pre-EnumMacros name and implicit nullable signatures.
$root = dirname(__DIR__).'/vendor/jobmetric/domi/src';
if (! is_dir($root)) {
    exit(0);
}
foreach (glob($root.'/Enums/*.php') as $path) {
    $source = file_get_contents($path);
    $updated = str_replace('EnumToArray', 'EnumMacros', $source);
    if ($updated !== $source) {
        file_put_contents($path, $updated);
    }
}
foreach ([$root.'/Domi.php', $root.'/helpers.php'] as $path) {
    $source = file_get_contents($path);
    $updated = preg_replace('/(?<![?\\w])string (\$\w+ = null)/', '?string $1', $source);
    if ($source !== $updated) {
        file_put_contents($path, $updated);
    }
}
