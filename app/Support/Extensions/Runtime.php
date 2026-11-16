<?php

declare(strict_types=1);

namespace App\Support\Extensions;

use Closure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

/**
 * Persist CMS activation policy independently of extension-owned business data.
 */
class Runtime
{
    /**
     * Resolve the private runtime directory.
     *
     * @return string
     */
    public function directory(): string
    {
        return (string) config('extensions.runtime_path');
    }

    /**
     * Resolve CLI PHP from explicit configuration or trusted web-runtime directories.
     * FastCGI may expose a CGI binary and omit PHP from PATH; its sibling CLI is still usable.
     *
     * @return string
     * @throws ValidationException
     */
    public function phpBinary(): string
    {
        $configured = config('extensions.php_binary');
        $candidates = $configured ? [$configured] : [];
        if (! $configured) {
            $executable = PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php';
            $candidates[] = dirname(PHP_BINARY).DIRECTORY_SEPARATOR.$executable;
            if ($ini = php_ini_loaded_file()) {
                $candidates[] = dirname($ini).DIRECTORY_SEPARATOR.$executable;
            }
            $candidates[] = app(PhpExecutableFinder::class)->find(false);
        }
        foreach (array_unique(array_filter($candidates)) as $candidate) {
            if (is_string($candidate) && is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        throw ValidationException::withMessages(['archive' => trans('extensions.php_unavailable')]);
    }

    /**
     * Forward only explicit bootstrap context that web SAPIs may omit from inherited process variables.
     *
     * @return array<string, string>
     */
    public function workerEnvironment(): array
    {
        $environment = ['EXTENSIONS_SAFE_MODE' => 'true', 'APP_ENV' => app()->environment(), 'LARAVEL_STORAGE_PATH' => storage_path()];
        $prefix = rtrim(base_path(), '/\\').DIRECTORY_SEPARATOR;
        foreach (['APP_CONFIG_CACHE' => app()->getCachedConfigPath(), 'APP_ROUTES_CACHE' => app()->getCachedRoutesPath()] as $key => $path) {
            // Relative paths also avoid Laravel treating Windows drive paths as application-relative twice.
            $environment[$key] = str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path;
        }

        return $environment;
    }

    /**
     * Build the isolated upgrade command from the deployment's CLI executable and entrypoint.
     *
     * @param  string  $namespace
     *
     * @return list<string>
     * @throws ValidationException
     */
    public function upgradeCommand(string $namespace): array
    {
        return [$this->phpBinary(), base_path('artisan'), 'extensions:migrate', $namespace];
    }

    /**
     * Read activation overrides, failing closed if the state file is damaged.
     *
     * @return array<string, array{active: bool, revision: string}>
     * @throws RuntimeException
     */
    public function states(): array
    {
        $path = $this->directory().'/states.json';
        if (! is_file($path)) {
            return [];
        }
        $states = json_decode((string) file_get_contents($path), true);
        if (! is_array($states)) {
            throw new RuntimeException('Invalid extension activation state. Use EXTENSIONS_SAFE_MODE=true to recover.');
        }

        return $states;
    }

    /**
     * Return historical owner identities without making the route guard fail on corrupt state.
     *
     * @return array<int, string>
     */
    public function knownNamespaces(): array
    {
        try {
            return array_keys($this->states());
        } catch (RuntimeException) {
            return [];
        }
    }

    /**
     * Detect recovery before discovery, including deployments using cached configuration.
     *
     * @return bool
     */
    public function recovering(): bool
    {
        return (bool) config('extensions.safe_mode')
            || filter_var(getenv('EXTENSIONS_SAFE_MODE'), FILTER_VALIDATE_BOOL)
            || in_array('extensions:disable', $_SERVER['argv'] ?? [], true);
    }

    /**
     * Allow legacy installations unless an explicit inactive override exists.
     *
     * @param  string  $namespace
     *
     * @return bool
     */
    public function active(string $namespace): bool
    {
        if ($this->recovering()) {
            return false;
        }
        try {
            $states = $this->states();
            $installedFile = (string) config('extensions.installed_file', storage_path('app/installed_extensions.json'));
            $installed = is_file($installedFile) ? json_decode((string) file_get_contents($installedFile), true) : [];
            if (! is_array($installed)) {
                return false;
            }

            return $this->enabled($namespace, $states, array_column($installed, null, 'namespace'));
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Suppress dependants while a dependency is disabled, missing or cyclic.
     *
     * @param  string  $namespace
     * @param  array<string, array{active: bool, revision: string}>  $states
     * @param  array<string, array<string, mixed>>  $installed
     * @param  array<int, string>  $trail
     *
     * @return bool
     */
    private function enabled(string $namespace, array $states, array $installed, array $trail = []): bool
    {
        if (($states[$namespace]['active'] ?? true) !== true || in_array($namespace, $trail, true)) {
            return false;
        }
        $trail[] = $namespace;
        $dependencies = $installed[$namespace]['info']['depends'] ?? [];
        if (! is_array($dependencies)) {
            return false;
        }
        $owner = $installed[$namespace]['info']['_owner'] ?? null;
        if (is_string($owner)) {
            $dependencies[] = $owner;
        }
        foreach ($dependencies as $dependency) {
            if (! is_string($dependency) || ! isset($installed[$dependency]) || ! $this->enabled($dependency, $states, $installed, $trail)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Atomically replace one activation override under the caller's operation lock.
     *
     * @param  string  $namespace
     * @param  bool  $active
     *
     * @return void
     * @throws Throwable
     */
    public function set(string $namespace, bool $active): void
    {
        $states = $this->states();
        $states[$namespace] = ['active' => $active, 'revision' => bin2hex(random_bytes(16))];
        File::ensureDirectoryExists($this->directory());
        $temporary = tempnam($this->directory(), '.state-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to create extension activation state.');
        }
        try {
            if (is_file($this->directory().'/states.json')) {
                chmod($temporary, fileperms($this->directory().'/states.json') & 0777);
            }
            if (file_put_contents($temporary, json_encode($states, JSON_THROW_ON_ERROR), LOCK_EX) === false
                || ! rename($temporary, $this->directory().'/states.json')) {
                throw new RuntimeException('Unable to persist extension activation state.');
            }
            Artisan::call('route:clear');
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * Serialize filesystem and database lifecycle operations across PHP workers.
     *
     * @param  Closure  $callback
     *
     * @return mixed
     * @throws Throwable
     */
    public function locked(Closure $callback): mixed
    {
        File::ensureDirectoryExists($this->directory());
        $handle = fopen($this->directory().'/operations.lock', 'c');
        if ($handle === false || ! flock($handle, LOCK_EX | LOCK_NB)) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            abort(409, trans('extensions.busy'));
        }
        try {
            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
