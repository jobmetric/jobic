<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

/** Fluent registration builder for one Editor.js tool driver. */
final class EditorDriverBuilder
{
    private string $label;
    private string $clientClass;
    private array $schema = [];
    private array $clientConfig = [];
    private ?Closure $validator = null;

    public function __construct(private readonly string $key, private readonly EditorDriverRegistry $registry)
    {
        $this->label = $key;
        $this->clientClass = ucfirst($key);
    }

    public function label(string $label): self { $this->label = $label; return $this; }
    public function clientClass(string $class): self { $this->clientClass = $class; return $this; }
    public function schema(array $schema): self { $this->schema = $schema; return $this; }
    public function clientConfig(array $config): self { $this->clientConfig = array_replace($this->clientConfig, $config); return $this; }
    public function script(string $url): self { $this->clientConfig['script'] = $url; return $this; }
    public function validateUsing(Closure $validator): self { $this->validator = $validator; return $this; }
    public function register(): EditorDriverRegistry
    {
        $this->registry->put(new EditorDriverDefinition($this->key, $this->label, $this->clientClass, $this->schema, $this->clientConfig, $this->validator));
        return $this->registry;
    }
}
