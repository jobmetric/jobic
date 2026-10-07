<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

/** Registry for Editor.js drivers; extensions register tools through builders. */
final class EditorDriverRegistry
{
    /** @var array<string, EditorDriverDefinition> */
    private array $drivers = [];

    public function register(string $key): EditorDriverBuilder
    {
        if (! preg_match('/^[a-z][a-zA-Z0-9_-]*$/', $key)) {
            throw new InvalidArgumentException('Editor driver keys must be valid identifiers.');
        }
        return new EditorDriverBuilder($key, $this);
    }

    public function put(EditorDriverDefinition $definition): void { $this->drivers[$definition->key] = $definition; }
    public function has(string $key): bool { return isset($this->drivers[$key]); }
    public function get(string $key): EditorDriverDefinition
    {
        return $this->drivers[$key] ?? throw new InvalidArgumentException("Editor driver [{$key}] is not registered.");
    }
    /** @return array<string, EditorDriverDefinition> */
    public function all(): array { return $this->drivers; }
    public function clientTools(): array
    {
        return collect($this->drivers)->mapWithKeys(fn (EditorDriverDefinition $driver) => [$driver->key => [
            'class' => $driver->clientClass,
            'label' => $driver->label,
        ] + $driver->clientConfig])->all();
    }
}
