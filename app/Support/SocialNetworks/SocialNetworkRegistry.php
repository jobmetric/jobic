<?php

declare(strict_types=1);

namespace App\Support\SocialNetworks;

use InvalidArgumentException;

/**
 * Register social-network definitions and resolve their public profile URLs.
 */
class SocialNetworkRegistry
{
    /** @var array<string, array{key: string, name: string, icon: string, url_template: string}> */
    private array $networks = [];

    /**
     * Register one uniquely keyed social-network definition.
     *
     * @param string $key
     * @param string $name
     * @param string $icon
     * @param string $urlTemplate
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function register(string $key, string $name, string $icon, string $urlTemplate): self
    {
        if (! preg_match('/^[a-z0-9][a-z0-9_-]*$/', $key)) {
            throw new InvalidArgumentException('Social-network keys may contain lowercase letters, numbers, underscores, and hyphens only.');
        }

        if (isset($this->networks[$key])) {
            throw new InvalidArgumentException("Social network [{$key}] is already registered.");
        }

        if (! str_starts_with($urlTemplate, 'https://') || substr_count($urlTemplate, '{slug}') !== 1) {
            throw new InvalidArgumentException('A social-network URL template must be HTTPS and contain exactly one {slug} placeholder.');
        }

        if (! preg_match('/^[a-z0-9][a-z0-9_-]*$/', $icon)) {
            throw new InvalidArgumentException('Social-network icons must reference a safe icon-sprite key.');
        }

        $this->networks[$key] = compact('key', 'name', 'icon') + ['url_template' => $urlTemplate];

        return $this;
    }

    /**
     * Return every registered network keyed by its stable identifier.
     *
     * @return array<string, array{key: string, name: string, icon: string, url_template: string}>
     */
    public function all(): array
    {
        return $this->networks;
    }

    /**
     * Resolve a registered definition by key.
     *
     * @param string $key
     *
     * @return array{key: string, name: string, icon: string, url_template: string}|null
     */
    public function get(string $key): ?array
    {
        return $this->networks[$key] ?? null;
    }

    /**
     * Replace the slug placeholder with a safely encoded public identifier.
     *
     * @param string $key
     * @param string $slug
     *
     * @return string|null
     */
    public function url(string $key, string $slug): ?string
    {
        $network = $this->get($key);

        return $network === null ? null : str_replace('{slug}', rawurlencode(ltrim($slug, '@')), $network['url_template']);
    }
}
