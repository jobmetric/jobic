<?php

declare(strict_types=1);

namespace App\Support\Extensions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Validation\ValidationException;
use JobMetric\Extension\Facades\ExtensionTypeRegistry;
use JobMetric\Extension\Support\ExtensionComposition;
use ZipArchive;

/**
 * Stage one extension archive without executing its PHP files.
 */
class Archive
{
    /**
     * Extract bounded regular files and verify the package's existing manifest layout.
     *
     * @param  string  $path
     * @param  string  $directory
     *
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function stage(string $path, string $directory): array
    {
        $zip = new ZipArchive;
        if (filesize($path) > config('extensions.zip_max_bytes') || $zip->open($path) !== true) {
            throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
        }
        File::ensureDirectoryExists($directory);
        try {
            $total = 0;
            $seen = [];
            if ($zip->numFiles > config('extensions.max_files')) {
                throw ValidationException::withMessages(['archive' => trans('extensions.archive_limit')]);
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $name = $entry['name'];
                $zip->getExternalAttributesIndex($i, $system, $attributes);
                $mode = ($attributes >> 16) & 0170000;
                if (preg_match('~(^/|\\\\|:|\x00|(^|/)\.\.?(/|$))~', $name)
                    || preg_match('~[\x00-\x1f<>"|?*]|[ .](/|$)|(^|/)(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.[^/]*)?(/|$)~i', $name)
                    || in_array($mode, [0120000, 0060000, 0020000, 0010000, 0140000], true)
                    || isset($seen[strtolower(rtrim($name, '/'))])) {
                    throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                }
                $seen[strtolower(rtrim($name, '/'))] = true;
                $total += $entry['size'];
                if ($total > config('extensions.extracted_max_bytes')) {
                    throw ValidationException::withMessages(['archive' => trans('extensions.archive_limit')]);
                }
                $parent = dirname($directory.'/'.$name);
                for ($cursor = $parent; strlen($cursor) > strlen($directory); $cursor = dirname($cursor)) {
                    if (file_exists($cursor) && ! is_dir($cursor)) {
                        throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                    }
                }
                if (str_ends_with($name, '/')) {
                    File::ensureDirectoryExists($directory.'/'.$name);

                    continue;
                }
                if (is_dir($directory.'/'.$name)) {
                    throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                }
                File::ensureDirectoryExists($parent);
                $source = $zip->getStream($name);
                $target = fopen($directory.'/'.$name, 'xb');
                if (! is_resource($source) || ! is_resource($target)) {
                    throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                }
                try {
                    $copied = stream_copy_to_stream($source, $target, $entry['size'] + 1);
                    if ($copied !== $entry['size']) {
                        throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                    }
                } finally {
                    fclose($source);
                    fclose($target);
                }
            }
            $manifests = collect(File::allFiles($directory))->filter(fn ($file): bool => $file->getFilename() === 'extension.json')->sortBy(fn ($file) => strlen($file->getPathname()))->values();
            if ($manifests->isEmpty()) {
                throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
            }
            if (filesize($manifests[0]->getPathname()) > 262144) {
                throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
            }
            $manifest = json_decode(File::get($manifests[0]->getPathname()), true);
            if (! is_array($manifest)) {
                throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
            }
            foreach (['extension', 'name', 'version', 'title'] as $key) {
                if (! isset($manifest[$key]) || ! is_string($manifest[$key]) || $manifest[$key] === '') {
                    throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                }
            }
            if (! preg_match('/^[A-Z][A-Za-z0-9]*$/', $manifest['name'])
                || ! preg_match('/^[A-Z][A-Za-z0-9]*$/', $manifest['extension'])
                || ! ExtensionTypeRegistry::has($manifest['extension'])
                || ! preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $manifest['version'])) {
                throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
            }
            $base = dirname($manifests[0]->getPathname());
            if (! is_file($base.'/'.$manifest['name'].'.php') || ! is_array($manifest['depends'] ?? [])) {
                throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
            }
            foreach ($manifest['depends'] ?? [] as $dependency) {
                if (! is_string($dependency)) {
                    throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                }
            }
            $expected = trim(app()->getNamespace(), '\\').'\\Extensions\\'.$manifest['extension'].'\\'.$manifest['name'].'\\'.$manifest['name'];
            foreach (File::allFiles($directory) as $file) {
                if (! str_starts_with(str_replace('\\', '/', $file->getPathname()), str_replace('\\', '/', $base).'/')) {
                    throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
                }
            }
            if (count($manifests) > 1 || ! empty($manifest['children'])) {
                $this->lint($base, $manifest['name'], $expected, $this->composition($base, $manifest));
            } else {
                $this->lint($base, $manifest['name'], $expected);
            }
            $manifest['inspection'] = $this->report($base, $manifest, (int) filesize($path));
            $manifest['staged_path'] = $base;

            return $manifest;
        } finally {
            $zip->close();
        }
    }

    /**
     * Validate declared child types through the package's public composition contract.
     *
     * @param  string  $base
     * @param  array<string, mixed>  $manifest
     *
     * @return array<int, array<string, mixed>>
     * @throws ValidationException
     */
    public function composition(string $base, array $manifest): array
    {
        if (! class_exists(ExtensionComposition::class)) {
            throw ValidationException::withMessages(['archive' => trans('extensions.composition_requires_update')]);
        }
        $namespace = trim(app()->getNamespace(), '\\').'\\Extensions\\'.$manifest['extension'].'\\'.$manifest['name'].'\\'.$manifest['name'];
        try {
            return (new ExtensionComposition)->inspect($base, $namespace);
        } catch (\InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
        }
    }

    /**
     * Parse uploaded PHP without requiring it, with bounded child-process memory and execution time.
     *
     * @param  string  $base
     * @param  string  $name
     * @param  string  $expected
     * @param  array<int, array<string, mixed>>  $entries
     *
     * @return void
     * @throws \Throwable
     */
    private function lint(string $base, string $name, string $expected, array $entries = []): void
    {
        $targets = [str_replace('\\', '/', $base.'/'.$name.'.php') => $expected];
        foreach ($entries as $item) {
            $targets[str_replace('\\', '/', $item['path'].'/'.$item['info']['name'].'.php')] = $item['namespace'];
        }
        $script = <<<'PHP'
$targets = json_decode($argv[2], true, flags: JSON_THROW_ON_ERROR);
$matched = [];
$declared = [];
try {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($argv[1], FilesystemIterator::SKIP_DOTS)) as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php' || str_ends_with($file->getFilename(), '.blade.php')) { continue; }
        $tokens = token_get_all(file_get_contents($file->getPathname()), TOKEN_PARSE);
        $entry = str_replace('\\', '/', $file->getPathname());
        if (!isset($targets[$entry])) { continue; }
        $declared = [];
        $namespace = '';
        for ($i = 0; $i < count($tokens); $i++) {
            if (!is_array($tokens[$i])) { continue; }
            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < count($tokens) && !in_array($tokens[$j], [';', '{'], true); $j++) {
                    if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) { $namespace .= $tokens[$j][1]; }
                }
            }
            if ($tokens[$i][0] === T_CLASS) {
                $j = $i + 1;
                while (isset($tokens[$j]) && is_array($tokens[$j]) && in_array($tokens[$j][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) { $j++; }
                if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) { $declared[] = $namespace.'\\'.$tokens[$j][1]; }
            }
        }
        if (in_array(strtolower($targets[$entry]), array_map('strtolower', $declared), true)) { $matched[$entry] = true; }
        unset($tokens);
    }
} catch (Throwable) { exit(1); }
exit(count($matched) === count($targets) ? 0 : 1);
PHP;
        $result = Process::timeout(20)->run([app(Runtime::class)->phpBinary(), '-d', 'memory_limit=64M', '-r', $script, $base, json_encode($targets, JSON_THROW_ON_ERROR)]);
        if (! $result->successful()) {
            throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
        }
    }

    /**
     * Export every staged file and its digest without exposing server paths or executable contents.
     *
     * @param  string  $base
     * @param  array<string, mixed>  $manifest
     * @param  int  $archiveBytes
     *
     * @return array<string, mixed>
     * @throws \Throwable
     */
    public function report(string $base, array $manifest, int $archiveBytes): array
    {
        unset($manifest['staged_path'], $manifest['inspection']);
        $files = [];
        $directories = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $file) {
            if ($file->isLink()) {
                throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
            }
            $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
            if ($file->isDir()) {
                $directories[] = $relative;
            } elseif ($file->isFile()) {
                $files[] = ['path' => $relative, 'bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getPathname())];
            } else {
                throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
            }
        }
        usort($files, fn ($a, $b) => strcmp($a['path'], $b['path']));
        sort($directories);

        $composition = [];
        if (! empty($manifest['children'])) {
            foreach ($this->composition($base, $manifest) as $item) {
                $composition[] = ['namespace' => $item['namespace'], 'owner' => $item['owner'],
                    'type' => $item['info']['extension'], 'name' => $item['info']['name'], 'version' => $item['info']['version']];
            }
        }

        return ['composition' => $composition, 'manifest' => $manifest, 'files' => $files, 'directories' => $directories,
            'file_count' => count($files), 'total_bytes' => array_sum(array_column($files, 'bytes')), 'archive_bytes' => $archiveBytes];
    }

    /**
     * Check the executable contract in a fresh CLI process only after explicit confirmation.
     *
     * @param  array<string, mixed>  $manifest
     * @param  string  $base
     *
     * @return void
     * @throws \Throwable
     */
    public function verify(array $manifest, string $base): void
    {
        $plan = ! empty($manifest['children']) ? $this->composition($base, $manifest) : [];
        $namespace = trim(app()->getNamespace(), '\\').'\\Extensions\\'.$manifest['extension'].'\\'.$manifest['name'].'\\'.$manifest['name'];
        $entries = $plan ? array_column($plan, 'namespace') : [$namespace];
        $prefix = substr($namespace, 0, (int) strrpos($namespace, '\\'));
        $script = <<<'PHP'
$loader = require $argv[1];
$prefix = $argv[2]."\\";
$loader->setClassMapAuthoritative(false);
$loader->addPsr4($prefix, $argv[3], true);
$map = [$argv[4] => $argv[3]."/".substr($argv[4], strrpos($argv[4], "\\") + 1).".php"];
foreach ($loader->getClassMap() as $class => $file) {
    if (str_starts_with($class, $prefix)) {
        $map[$class] = $argv[3]."/".str_replace("\\", "/", substr($class, strlen($prefix))).".php";
    }
}
$loader->addClassMap($map);
foreach (json_decode($argv[5], true, flags: JSON_THROW_ON_ERROR) as $entry) {
    if (!is_subclass_of($entry, "JobMetric\\Extension\\Contracts\\AbstractExtension")) { exit(1); }
}
exit(0);
PHP;
        $result = Process::timeout(20)->run([app(Runtime::class)->phpBinary(), '-r', $script, base_path('vendor/autoload.php'), $prefix, $base, $namespace, json_encode($entries, JSON_THROW_ON_ERROR)]);
        if (! $result->successful()) {
            throw ValidationException::withMessages(['archive' => trans('extensions.invalid_archive')]);
        }
    }
}
