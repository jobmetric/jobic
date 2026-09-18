<?php

declare(strict_types=1);

namespace App\Support\Installation;

use Closure;
use Dotenv\Dotenv;
use JobMetric\EnvModifier\EnvModifier;
use JobMetric\EnvModifier\Exceptions\EnvFileNotFoundException;
use Random\RandomException;
use RuntimeException;

/**
 * Manage atomic environment file updates.
 */
readonly class Environment
{
    /**
     * Initialize the service dependencies.
     *
     * @param string $path
     * @param string|null $cachePath
     *
     * @return void
     */
    public function __construct(public string $path, private ?string $cachePath = null)
    {
    }

    /**
     * Read environment values from the configured file.
     *
     * @return array<string, mixed>
     */
    public function read(): array
    {
        return is_file($this->path) ? Dotenv::parse(file_get_contents($this->path)) : [];
    }

    /**
     * Determine whether installation is marked complete.
     *
     * @return bool
     */
    public function installed(): bool
    {
        return ! in_array($this->read()['INSTALLATION'] ?? '', [
            '',
            'null',
            '(null)',
            'false',
            '(false)',
            '0',
        ], true);
    }

    /**
     * Run a callback while holding the environment file lock.
     *
     * @param Closure $callback
     *
     * @return mixed
     */
    public function locked(Closure $callback): mixed
    {
        $lock = fopen($this->path . '.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Environment lock is not writable.');
        }

        try {
            if (! flock($lock, LOCK_EX)) {
                throw new RuntimeException('Cannot lock environment.');
            }

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Create the environment file and application key when required.
     *
     * @param string $example
     *
     * @return void
     */
    public function initialize(string $example): void
    {
        $this->locked(function () use ($example): void {
            if (! is_file($this->path)) {
                $this->replace(file_get_contents($example));
            }
        });
    }

    /**
     * Save allowed environment values with atomic replacement.
     *
     * @param array<string, mixed> $values
     *
     * @return void
     * @throws EnvFileNotFoundException
     * @throws RandomException
     */
    public function write(array $values): void
    {
        $this->locked(function () use ($values): void {
            $temporary = tempnam(dirname($this->path), '.env-install-');
            if ($temporary === false) {
                throw new RuntimeException('Cannot create environment temporary file.');
            }

            try {
                chmod($temporary, 0600);
                if (! copy($this->path, $temporary)) {
                    throw new RuntimeException('Cannot read environment.');
                }

                // Modify a private sibling, then atomically replace the live file.
                // Unique placeholders avoid the package's regex replacement interpreting $1
                // or backslashes in credentials. Dotenv literals are encoded afterwards.
                $placeholders = [];
                $replacements = [];
                foreach ($values as $key => $value) {
                    if (! preg_match('/^[A-Z][A-Z0-9_]*$/D', $key)) {
                        throw new RuntimeException('Invalid environment key.');
                    }

                    $placeholder = 'JOBIC_' . bin2hex(random_bytes(24));
                    $placeholders[$key] = $placeholder;
                    $literal = is_bool($value) ? ($value ? 'true' : 'false') : ($value === null ? 'null' : (string) $value);
                    $replacements[$placeholder] = '"' . str_replace([
                            '\\',
                            '"',
                            '$',
                            "\r",
                            "\n",
                        ], [
                            '\\\\',
                            '\\"',
                            '\\$',
                            '\\r',
                            '\\n',
                        ], $literal) . '"';
                }

                (new EnvModifier)->setPath($temporary)->set($placeholders);

                if (file_put_contents($temporary, strtr(file_get_contents($temporary), $replacements), LOCK_EX) === false) {
                    throw new RuntimeException('Cannot write environment.');
                }

                $parsed = Dotenv::parse(file_get_contents($temporary));
                foreach ($values as $key => $value) {
                    $expected = is_bool($value) ? ($value ? 'true' : 'false') : ($value === null ? 'null' : (string) $value);
                    if (($parsed[$key] ?? null) !== $expected) {
                        throw new RuntimeException('Environment value could not be encoded.');
                    }
                }

                $this->invalidateCache();
                if (! rename($temporary, $this->path)) {
                    throw new RuntimeException('Cannot replace environment.');
                }
            } finally {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        });
    }

    /**
     * Remove cached configuration after an environment change.
     *
     * @return void
     */
    public function invalidateCache(): void
    {
        if ($this->cachePath && is_file($this->cachePath) && ! unlink($this->cachePath)) {
            throw new RuntimeException('Configuration cache is not writable.');
        }
    }

    /**
     * Atomically replace the environment file contents.
     *
     * @param string $contents
     *
     * @return void
     */
    private function replace(string $contents): void
    {
        $temporary = tempnam(dirname($this->path), '.env-install-');
        if ($temporary === false) {
            throw new RuntimeException('Cannot create environment.');
        }

        try {
            chmod($temporary, 0600);
            if (file_put_contents($temporary, $contents, LOCK_EX) === false || ! rename($temporary, $this->path)) {
                throw new RuntimeException('Cannot initialize environment.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
