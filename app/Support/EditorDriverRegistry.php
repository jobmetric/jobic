<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\EditorBlockDriver;
use InvalidArgumentException;

/** Registry for Editor.js drivers; extensions register tools through builders. */
final class EditorDriverRegistry
{
    /** @var array<string, EditorDriverDefinition> */
    private array $drivers = [];

    /** @var array<string, true> */
    private array $paths = [];

    /** @var array<string, array<string, string>> */
    private array $assets = [];

    /**
     * Render an Editor.js document using registered output views.
     *
     * @param array<string, mixed>|string|null $document
     *
     * @return string
     */
    public function render(array|string|null $document): string
    {
        return app(EditorRenderer::class)->render($document);
    }

    /**
     * Resolve the template registry associated with this editor.
     *
     * @return EditorTemplateRegistry
     */
    public function templates(): EditorTemplateRegistry
    {
        return app(EditorTemplateRegistry::class);
    }

    /**
     * Discover independent driver folders from a trusted local directory.
     *
     * @param string $path
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function addPath(string $path): self
    {
        $root = realpath($path);
        if ($root === false || ! is_dir($root)) {
            throw new InvalidArgumentException('Editor driver directory does not exist.');
        }

        if (isset($this->paths[$root])) {
            return $this;
        }

        foreach (glob($root . '/*/driver.json') ?: [] as $manifest) {
            $directory = realpath(dirname($manifest));

            if ($directory === false || ! str_starts_with($directory, $root . DIRECTORY_SEPARATOR)) {
                throw new InvalidArgumentException('Editor driver directory escapes its registered root.');
            }

            $metadata = json_decode(file_get_contents($manifest), true);
            $key = $metadata['key'] ?? null;
            $class = $metadata['class'] ?? null;

            if (! is_string($key) || ! is_string($class) || ! is_subclass_of($class, EditorBlockDriver::class)) {
                throw new InvalidArgumentException('Invalid editor driver manifest: ' . $manifest);
            }

            $builder = $this->register($key);
            if ($this->has($key)) {
                throw new InvalidArgumentException("Editor driver [{$key}] is already registered.");
            }

            app($class)->configure($builder);

            $assets = [];
            foreach (
                [
                    'module' => 'js',
                    'style'  => 'css',
                ] as $kind => $extension) {

                if (! isset($metadata[$kind])) {
                    continue;
                }

                if (! is_string($metadata[$kind])) {
                    throw new InvalidArgumentException('Invalid editor asset: ' . $key);
                }

                $file = realpath($directory . DIRECTORY_SEPARATOR . $metadata[$kind]);
                if ($file === false || ! is_file($file) || ! str_starts_with($file, $directory . DIRECTORY_SEPARATOR) || pathinfo($file, PATHINFO_EXTENSION) !== $extension) {
                    throw new InvalidArgumentException('Invalid editor asset: ' . $key);
                }

                $assets[$kind] = $file;
                $builder->clientConfig([$kind => url('/editor-assets/' . $key . '/' . $kind) . '?v=' . hash_file('sha256', $file)]);
            }

            if (isset($metadata['view'])) {
                if (! is_string($metadata['view'])) {
                    throw new InvalidArgumentException('Invalid editor view: ' . $key);
                }

                $view = realpath($directory . DIRECTORY_SEPARATOR . $metadata['view']);

                if ($view === false || ! is_file($view) || ! str_starts_with($view, $directory . DIRECTORY_SEPARATOR) || ! str_ends_with($view, '.blade.php')) {
                    throw new InvalidArgumentException('Invalid editor view: ' . $key);
                }

                app('view')->addNamespace('editor_' . $key, dirname($view));

                $builder->view('editor_' . $key . '::' . basename($view, '.blade.php'));
            }

            $builder->register();
            $this->assets[$key] = $assets;
        }
        $this->paths[$root] = true;

        return $this;
    }

    /**
     * Resolve only an explicitly registered public browser asset.
     *
     * @param string $key
     * @param string $kind
     *
     * @return string|null
     */
    public function asset(string $key, string $kind): ?string
    {
        return $this->assets[$key][$kind] ?? null;
    }

    /**
     * Start a fluent definition for a valid block identifier.
     *
     * @param string $key
     *
     * @return EditorDriverBuilder
     * @throws InvalidArgumentException
     */
    public function register(string $key): EditorDriverBuilder
    {
        if (! preg_match('/^[a-z][a-zA-Z0-9_-]*$/', $key)) {
            throw new InvalidArgumentException('Editor driver keys must be valid identifiers.');
        }

        return new EditorDriverBuilder($key, $this);
    }

    /**
     * Store a completed definition without replacing an existing driver.
     *
     * @param EditorDriverDefinition $definition
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function put(EditorDriverDefinition $definition): void
    {
        if ($this->has($definition->key)) {
            throw new InvalidArgumentException("Editor driver [{$definition->key}] is already registered.");
        }
        $this->drivers[$definition->key] = $definition;
    }
    /**
     * Determine whether a block driver is registered.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->drivers[$key]);
    }

    /**
     * Resolve a registered block definition.
     *
     * @param string $key
     *
     * @return EditorDriverDefinition
     * @throws InvalidArgumentException
     */
    public function get(string $key): EditorDriverDefinition
    {
        return $this->drivers[$key] ?? throw new InvalidArgumentException("Editor driver [{$key}] is not registered.");
    }
    /**
     * Return definitions indexed by block identifier.
     *
     * @return array<string, EditorDriverDefinition>
     */
    public function all(): array
    {
        return $this->drivers;
    }

    /**
     * Export browser tool definitions without server validators or file paths.
     *
     * @return array<string, array<string, mixed>>
     */
    public function clientTools(): array
    {
        return collect($this->drivers)
            ->mapWithKeys(fn (EditorDriverDefinition $driver) => [
                $driver->key => [
                        'class' => $driver->clientClass,
                        'label' => $driver->label,
                    ] + $driver->clientConfig + $driver->presentation,
            ])
            ->all();
    }
}
