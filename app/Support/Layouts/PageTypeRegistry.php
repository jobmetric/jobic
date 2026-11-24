<?php

declare(strict_types=1);

namespace App\Support\Layouts;

use InvalidArgumentException;

/**
 * Store the page types available to the site layout editor.
 */
class PageTypeRegistry
{
    /** @var array<string, string> */
    private array $types = [];

    /**
     * Register one stable page-type key and translated label.
     *
     * @param string $key
     * @param string $label
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function register(string $key, string $label): self
    {
        $key = strtolower(trim($key));
        if ($key === '' || preg_match('/^[a-z][a-z0-9._-]*$/', $key) !== 1 || trim($label) === '') {
            throw new InvalidArgumentException('Invalid layout page type.');
        }
        $this->types[$key] = $label;

        return $this;
    }

    /**
     * Determine whether the page type is registered.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->types[strtolower(trim($key))]);
    }

    /**
     * Return translated page-type labels keyed by their stable identifiers.
     *
     * @return array<string, string>
     */
    public function translated(): array
    {
        return collect($this->types)->mapWithKeys(fn (string $label, string $key): array => [$key => trans($label)])->all();
    }
}
