<?php

// Build only repository-owned examples; this script never touches vendor or live extensions.
$catalog = json_decode(file_get_contents(__DIR__.'/catalog.json'), true, flags: JSON_THROW_ON_ERROR);
foreach ($catalog as $example) {
    $root = __DIR__.'/'.$example['type'].'/'.$example['name'];
    $name = basename($root);
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile()) {
            $files[] = $file->getPathname();
        }
    }
    sort($files);
    foreach (['1.0.0', '1.1.0'] as $version) {
        $path = __DIR__.'/dist/'.$name.($version === '1.0.0' ? '' : '-'.$version).'.zip';
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to build example archive.');
        }
        foreach ($files as $file) {
            $relative = str_replace('\\', '/', substr($file, strlen($root) + 1));
            if (basename($relative) === 'extension.json') {
                $manifest = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
                $manifest['version'] = $version;
                $zip->addFromString($name.'/'.$relative, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");
            } else {
                $zip->addFile($file, $name.'/'.$relative);
            }
        }
        $zip->close();
    }
}
