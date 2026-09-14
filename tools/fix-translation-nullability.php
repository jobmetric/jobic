<?php

declare(strict_types=1);

// Run before Composer generates/loads the application's autoloader.
// Remove this compatibility patch once the upstream release has explicit nullable types.
$path = dirname(__DIR__).'/vendor/jobmetric/laravel-translation/src/helpers.php';
if (! is_file($path)) {
    fwrite(STDERR, "Translation helpers are missing; install Composer dependencies first.\n");
    exit(1);
}
$source = file_get_contents($path);
$updated = $source;
foreach ([
    'function translationResourceData(iterable $relations, string $locale = null): array',
    'function translationDataSelect(EloquentCollection $objects, string $field, string $locale = null): Collection',
] as $signature) {
    $fixed = str_replace('string $locale = null', '?string $locale = null', $signature);
    if (str_contains($updated, $signature)) {
        $updated = str_replace($signature, $fixed, $updated);
    } elseif (! str_contains($updated, $fixed)) {
        fwrite(STDERR, "Translation helper signatures changed; review the nullability compatibility patch.\n");
        exit(1);
    }
}
if ($updated !== $source && file_put_contents($path, $updated, LOCK_EX) === false) {
    fwrite(STDERR, "Cannot apply the translation compatibility patch.\n");
    exit(1);
}
