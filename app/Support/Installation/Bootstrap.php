<?php

declare(strict_types=1);

namespace App\Support\Installation;

use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Throwable;

class Bootstrap
{
    public static function prepare(Application $app): void
    {
        // CLI commands and tests never silently modify a developer's environment.
        if ($app->runningInConsole()) {
            return;
        }
        try {
            self::initialize($app);
        } catch (Throwable) {
            $app->instance('installation.bootstrap_error', true);
        }
    }

    public static function initialize(Application $app): void
    {
        $environment = new Environment($app->environmentFilePath(), $app->getCachedConfigPath());
        if ($environment->installed()) {
            return;
        }
        $lock = fopen($app->environmentFilePath().'.bootstrap.lock', 'c');
        if ($lock === false) {
            throw new \RuntimeException('Cannot lock environment initialization.');
        }
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Cannot lock environment initialization.');
            }
            $environment = new Environment($app->environmentFilePath(), $app->getCachedConfigPath());
            $exists = is_file($environment->path);
            $environment->initialize($app->basePath('.env.example'));
            $values = $environment->read();
            if ($environment->installed()) {
                return;
            }
            $environment->invalidateCache();
            $changes = [];
            if (! $exists) {
                $changes = ['INSTALLATION_STATUS' => 'pending', 'INSTALLATION_STEP' => 0, 'INSTALLATION_TASK' => 0];
            }
            if (empty($values['APP_KEY'])) {
                $changes['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
            }
            if (! empty($values['APP_KEY'])) {
                $key = str_starts_with($values['APP_KEY'], 'base64:') ? base64_decode(substr($values['APP_KEY'], 7), true) : $values['APP_KEY'];
                if (! is_string($key) || ! Encrypter::supported($key, 'AES-256-CBC')) {
                    throw new \RuntimeException('Existing application key is invalid; restore it before continuing.');
                }
            }
            if ($changes !== []) {
                $environment->write($changes);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
