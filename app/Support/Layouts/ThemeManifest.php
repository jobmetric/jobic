<?php

declare(strict_types=1);

namespace App\Support\Layouts;

use ReflectionClass;
use Illuminate\Support\Str;
use Throwable;

/**
 * Read and validate the layout positions and display options owned by a Theme extension.
 */
class ThemeManifest
{
    /**
     * Return a validated manifest or null when the Theme does not meet the contract.
     *
     * @param string $namespace
     *
     * @return array{schema: int, positions: array<string, string>, menu_views?: array<string, string>}|null
     */
    public function read(string $namespace): ?array
    {
        try {
            if (! class_exists($namespace)) {
                return null;
            }
            $file = (new ReflectionClass($namespace))->getFileName();
            $path = dirname((string) $file).DIRECTORY_SEPARATOR.'layout.json';
            if (! is_file($path)) {
                return null;
            }

            return $this->readFile($path);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Return a validated manifest from an explicit trusted extension path.
     *
     * @param string $path
     *
     * @return array{schema: int, positions: array<string, string>, menu_views?: array<string, string>}|null
     */
    public function readFile(string $path): ?array
    {
        try {
            if (! is_file($path)) {
                return null;
            }
            $manifest = json_decode((string) file_get_contents($path), true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($manifest) || array_diff(array_keys($manifest), ['schema', 'positions', 'menu_views']) !== []
                || ($manifest['schema'] ?? null) !== 1 || ! is_array($manifest['positions'] ?? null)
                || $manifest['positions'] === []) {
                return null;
            }
            foreach (['positions', 'menu_views'] as $section) {
                $entries = $manifest[$section] ?? [];
                if (! is_array($entries) || (array_key_exists($section, $manifest) && $manifest[$section] === null)) {
                    return null;
                }
                foreach ($entries as $key => $label) {
                    if (! is_string($key) || preg_match('/^[a-z][a-z0-9_]*$/', $key) !== 1
                        || ! is_string($label) || trim($label) === '') {
                        return null;
                    }
                }
            }

            return $manifest;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Return the ordered translated positions for a valid Theme.
     *
     * @param string $namespace
     *
     * @return array<string, string>
     */
    public function positions(string $namespace): array
    {
        $manifest = $this->read($namespace);

        return collect($manifest['positions'] ?? [])->mapWithKeys(function (string $label, string $key): array {
            $translated = trans($label);

            return [$key => $translated === $label ? Str::headline($key) : $translated];
        })->all();
    }
}
