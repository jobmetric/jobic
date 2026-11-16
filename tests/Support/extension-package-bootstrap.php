<?php

// Test-only source override. Deployment continues to resolve Composer's published releases.
$loader = require dirname(__DIR__, 2).'/vendor/autoload.php';
if ($source = getenv('JOBIC_EXTENSION_PACKAGE_SOURCE')) {
    $prefix = 'JobMetric\\Extension\\';
    $loader->addPsr4($prefix, rtrim($source, '/\\').'/src', true);
    $map = [];
    foreach ($loader->getClassMap() as $class => $file) {
        if (str_starts_with($class, $prefix)) {
            $replacement = rtrim($source, '/\\').'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
            if (is_file($replacement)) {
                $map[$class] = $replacement;
            }
        }
    }
    $loader->addClassMap($map);
}
