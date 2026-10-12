<?php

declare(strict_types=1);

namespace App\Support;

use Closure;
use InvalidArgumentException;

/** Configure one block for validation, insertion, settings and output. */
final class EditorDriverBuilder
{
    private string $label;
    private string $clientClass;
    private array $schema = [];
    private array $clientConfig = [];
    private array $presentation = [];
    private ?Closure $validator = null;

    /**
     * Start a definition in the supplied registry.
     *
     * @param string $key
     * @param EditorDriverRegistry $registry
     *
     * @return void
     */
    public function __construct(private readonly string $key, private readonly EditorDriverRegistry $registry)
    {
        $this->label = $key;
        $this->clientClass = ucfirst($key);
    }

    /**
     * Set the translated label key.
     *
     * @param string $label
     *
     * @return self
     */
    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Select a registered browser tool class.
     *
     * @param string $class
     *
     * @return self
     */
    public function clientClass(string $class): self
    {
        $this->clientClass = $class;

        return $this;
    }

    /**
     * Declare attribute types, with a question mark for optional attributes.
     *
     * @param array<string, string> $schema
     *
     * @return self
     */
    public function schema(array $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * Merge browser tool options.
     *
     * @param array<string, mixed> $config
     *
     * @return self
     */
    public function clientConfig(array $config): self
    {
        $this->clientConfig = array_replace($this->clientConfig, $config);

        return $this;
    }

    /**
     * Load a legacy browser tool script.
     *
     * @param string $url
     *
     * @return self
     */
    public function script(string $url): self
    {
        $this->clientConfig['script'] = $url;

        return $this;
    }

    /**
     * Apply additional server validation.
     *
     * @param Closure $validator
     *
     * @return self
     */
    public function validateUsing(Closure $validator): self
    {
        $this->validator = $validator;

        return $this;
    }

    /**
     * Group the tool in the block inserter.
     *
     * @param string $category
     *
     * @return self
     */
    public function category(string $category): self
    {
        $this->presentation['category'] = $category;

        return $this;
    }

    /**
     * Select a shared Jobic SVG icon.
     *
     * @param string $icon
     *
     * @return self
     */
    public function icon(string $icon): self
    {
        $this->presentation['icon'] = $icon;

        return $this;
    }

    /**
     * Set the translated tool description.
     *
     * @param string $description
     *
     * @return self
     */
    public function description(string $description): self
    {
        $this->presentation['description'] = $description;

        return $this;
    }

    /**
     * Set initial data for inserted blocks.
     *
     * @param array<string, mixed> $data
     *
     * @return self
     */
    public function defaults(array $data): self
    {
        $this->presentation['defaults'] = $data;

        return $this;
    }

    /**
     * Declare translated inspector controls.
     *
     * @param array<string, array<string, mixed>> $fields
     *
     * @return self
     */
    public function settings(array $fields): self
    {
        $this->presentation['settings'] = $fields;

        return $this;
    }

    /**
     * Select the namespaced Blade output view.
     *
     * @param string $view
     *
     * @return self
     */
    public function view(string $view): self
    {
        $this->presentation['view'] = $view;

        return $this;
    }

    /**
     * Declare data paths containing file-manager media objects.
     *
     * @param list<string> $paths
     *
     * @return self
     */
    public function mediaPaths(array $paths): self
    {
        $this->presentation['mediaPaths'] = $paths;

        return $this;
    }

    /**
     * Publish this definition to the shared registry.
     *
     * @return EditorDriverRegistry
     * @throws InvalidArgumentException
     */
    public function register(): EditorDriverRegistry
    {
        $this->registry->put(new EditorDriverDefinition($this->key, $this->label, $this->clientClass, $this->schema, $this->clientConfig, $this->validator, $this->presentation));

        return $this->registry;
    }
}
