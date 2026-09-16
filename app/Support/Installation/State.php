<?php

declare(strict_types=1);

namespace App\Support\Installation;

use Closure;
use JobMetric\PackageCore\Output\Response;
use RuntimeException;

/**
 * Store private installation state under a file lock.
 */
class State
{
    /**
     * Initialize the service dependencies.
     *
     * @param string $directory
     *
     * @return void
     */
    public function __construct(public readonly string $directory) {}

    /**
     * Run a callback while holding the installation state lock.
     *
     * @param Closure $callback
     *
     * @return mixed
     */
    public function locked(Closure $callback): mixed
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0700, true) && ! is_dir($this->directory)) {
            throw new RuntimeException('Installation storage is not writable.');
        }
        $lock = fopen($this->directory.'/state.lock', 'c');
        if ($lock === false) {
            throw new RuntimeException('Installation lock unavailable.');
        }
        try {
            if (! flock($lock, LOCK_EX | LOCK_NB)) {
                return Response::make(false, trans('installation.busy'), null, 409);
            }

            return $callback();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Read the private installation state.
     *
     * @return array<string, mixed>
     */
    public function read(): array
    {
        $path = $this->directory.'/state.json';

        return is_file($path) ? json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR) : [];
    }

    /**
     * Persist the private installation state.
     *
     * @param array<string, mixed> $state
     *
     * @return void
     */
    public function write(array $state): void
    {
        $temporary = tempnam($this->directory, 'state-');
        if ($temporary === false) {
            throw new RuntimeException('Installation storage unavailable.');
        }
        try {
            chmod($temporary, 0600);
            if (file_put_contents($temporary, json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX) === false || ! rename($temporary, $this->directory.'/state.json')) {
                throw new RuntimeException('Cannot save installation state.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * Remove temporary installation state.
     *
     * @return void
     */
    public function clear(): void
    {
        if (is_file($this->directory.'/state.json') && ! unlink($this->directory.'/state.json')) {
            throw new RuntimeException('Cannot remove installation state.');
        }
    }
}
