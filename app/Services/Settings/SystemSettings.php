<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Facades\Hooks;
use App\Http\Requests\Settings\CacheRequest;
use App\Services\Installation\Installation;
use App\Support\Installation\Connections;
use App\Support\Installation\Environment;
use App\Support\Installation\Fields;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use RuntimeException;
use Throwable;
use ZipArchive;

/** Manage optional cache configuration and read-only system diagnostics. */
readonly class SystemSettings
{
    /**
     * Reuse installation environment persistence and connection probes.
     *
     * @param Environment $environment
     * @param Connections $connections
     * @param MailSettings $mailSettings
     */
    public function __construct(
        private Environment $environment,
        private Connections $connections,
        private MailSettings $mailSettings
    ) {
    }

    /**
     * Describe supported cache fields without exposing stored passwords.
     *
     * @return array<string, mixed>
     */
    public function fields(): array
    {
        return [
                'CACHE_STORE' => [
                    'select',
                    'file',
                    [
                        'file',
                        'redis',
                    ],
                ],
            ] + Fields::groups(3)['redis'];
    }

    /**
     * Read safe configuration values for the settings form.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return Fields::values($this->fields(), $this->environment->read());
    }

    /**
     * Validate and probe a cache backend before atomically committing configuration.
     *
     * @param array<string, mixed> $data
     * @param int $userId
     * @param bool $save
     *
     * @return Response
     * @throws ValidationException
     * @throws Throwable
     */
    public function cache(array $data, int $userId, bool $save = true): Response
    {
        $values = dto($data, CacheRequest::class);

        if ($values['CACHE_STORE'] === 'redis') {
            $storedPassword = $this->environment->read()['REDIS_PASSWORD'] ?? '';
            $storedPassword = in_array($storedPassword, ['null', '(null)'], true) ? '' : $storedPassword;
            $values['REDIS_PASSWORD'] = ($values['REDIS_PASSWORD'] ?? '') ?: $storedPassword;
            $values += ['REDIS_CLIENT' => 'phpredis', 'REDIS_URL' => null];
        }

        $context = [
            'section' => 'cache',
            'user_id' => $userId,
            'fields'  => array_keys($values),
        ];

        if ($save) {
            // The default Redis connection is shared with sessions/queues. Do not strand active users.
            if (config('session.driver') === 'redis' || config('queue.default') === 'redis') {
                return Response::make(false, trans('system.redis_in_use'), null, 422);
            }

            Hooks::action('jobic.settings.cache.saving', $context);
        }

        if ($values['CACHE_STORE'] === 'redis') {
            try {
                $this->connections->redis($values);
            } catch (Throwable) {
                return Response::make(false, trans('system.redis_connection_failed'), null, 422);
            }
        }
        else {
            try {
                $this->probeDirectory(storage_path('framework/cache/data'));
            } catch (Throwable) {
                return Response::make(false, trans('system.file_cache_failed'), null, 422);
            }
        }

        try {
            if ($save) {
                $this->environment->write($values);
                Hooks::notify('jobic.settings.cache.saved', $context);
            }

            return Response::make(true, trans($save ? 'admin.saved' : 'system.cache_ok'));
        } catch (Throwable) {
            // No live manager is switched before persistence; failed writes retain the old backend.
            return Response::make(false, trans('system.configuration_write_failed'), null, 422);
        }
    }

    /**
     * Perform a private temporary read/write/delete check without touching customer data.
     *
     * @param string $directory
     *
     * @return void
     * @throws RuntimeException
     */
    private function probeDirectory(string $directory): void
    {
        if (! is_dir($directory) || ! is_writable($directory)) {
            throw new RuntimeException('Storage is not writable.');
        }

        $file = tempnam($directory, 'jobic-health-');
        if ($file === false) {
            throw new RuntimeException('Cannot create probe.');
        }

        try {
            if (file_put_contents($file, 'jobic') !== 5 || file_get_contents($file) !== 'jobic') {
                throw new RuntimeException('Storage probe failed.');
            }
        } finally {
            if (! unlink($file)) {
                throw new RuntimeException('Storage cleanup failed.');
            }
        }
    }

    /**
     * Report measured capabilities and explicitly unverified background execution.
     *
     * @return array<string, mixed>
     */
    public function health(): array
    {
        $gd = function_exists('gd_info') ? gd_info() : [];
        $checks = [];
        foreach (app(Installation::class)->requirements() as $key => $ready) {
            $checks['core_' . $key] = [
                'status' => $ready ? 'ready' : 'warning',
                'hint'   => 'core_hint',
                'label'  => str_starts_with($key, 'extension_') ? substr($key, 10) : trans('installation.checks.' . $key),
                'guide'  => 'guide_core',
            ];
        }

        foreach (
            [
                'gd'   => extension_loaded('gd'),
                'jpeg' => $gd['JPEG Support'] ?? false,
                'png'  => $gd['PNG Support'] ?? false,
                'webp' => $gd['WebP Support'] ?? false,
                'zip'  => class_exists(ZipArchive::class),
            ] as $key => $ready) {
            $checks[$key] = [
                'status' => $ready ? 'ready' : 'unavailable',
                'hint'   => 'optional_media',
                'guide'  => in_array($key, [
                    'gd',
                    'jpeg',
                    'png',
                    'webp',
                ], true) ? 'guide_gd' : 'guide_zip',
            ];
        }

        foreach (
            [
                'temporary'  => sys_get_temp_dir(),
                'storage'    => storage_path('app'),
                'sessions'   => (string) config('session.files'),
                'file_cache' => storage_path('framework/cache/data'),
            ] as $key => $path) {
            try {
                $this->probeDirectory($path);
                $checks[$key] = [
                    'status' => 'ready',
                    'hint'   => 'storage_ok',
                ];
            } catch (Throwable) {
                $checks[$key] = [
                    'status' => 'warning',
                    'hint'   => 'storage_failed',
                    'guide'  => 'guide_storage',
                ];
            }
        }

        $mailReady = false;
        try {
            $mailValues = $this->mailSettings->values();
            if (($mailValues['MAIL_MAILER'] ?? null) === 'smtp') {
                $mailReady = $this->mailSettings->test($mailValues + [
                        'action'    => 'connection',
                        'recipient' => null,
                    ])->ok;
            }
        } catch (Throwable) {
            // The health page reports the failed probe without exposing transport details.
        }

        $checks['mail'] = [
            'status' => $mailReady ? 'ready' : 'warning',
            'hint'   => $mailReady ? 'mail_ok' : 'mail_hint',
            'url'    => route('admin.settings.mail.edit'),
            'guide'  => 'guide_mail',
        ];

        $cacheReady = false;
        try {
            $cacheReady = $this->cache($this->values(), 0, false)->ok;
        } catch (Throwable) {
            // The health page reports the failed probe without exposing connection details.
        }

        $checks['cache'] = [
            'status' => $cacheReady ? 'ready' : 'warning',
            'hint'   => $cacheReady ? 'cache_ok' : 'cache_hint',
            'url'    => route('admin.settings.cache.edit'),
            'guide'  => 'guide_cache',
        ];
        $checks['jobs'] = [
            'status' => 'not_verified',
            'hint'   => 'jobs_hint',
            'guide'  => 'guide_jobs',
        ];
        $checks['scheduler'] = [
            'status' => 'not_verified',
            'hint'   => 'scheduler_hint',
            'guide'  => 'guide_scheduler',
        ];

        return [
            'checks'    => $checks,
            'checkedAt' => now()->toIso8601String(),
            'drivers'   => [
                'cache'    => config('cache.default'),
                'sessions' => config('session.driver'),
                'logging'  => config('logging.default'),
                'jobs'     => config('queue.default'),
            ],
        ];
    }
}
