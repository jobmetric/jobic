<?php

declare(strict_types=1);

namespace App\Support\MenuLinks;

use App\Contracts\MenuLinkDriver;
use InvalidArgumentException;

/** Hold menu-link drivers registered by the application or extensions. */
final class MenuLinkRegistry
{
    /** @var array<string, MenuLinkDriver> */
    private array $drivers = [];

    public function register(MenuLinkDriver $driver): self
    {
        $this->drivers[$driver->key()] = $driver;

        return $this;
    }

    public function get(string $key): MenuLinkDriver
    {
        return $this->drivers[$key] ?? throw new InvalidArgumentException("Unknown menu link driver [$key].");
    }

    public function has(string $key): bool
    {
        return isset($this->drivers[$key]);
    }

    /** @return array<string, MenuLinkDriver> */
    public function all(): array
    {
        return $this->drivers;
    }
}
