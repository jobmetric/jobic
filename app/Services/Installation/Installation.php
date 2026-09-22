<?php

declare(strict_types=1);

namespace App\Services\Installation;

use App\Http\Requests\Installation\DatabaseRequest;
use App\Http\Requests\Installation\LanguageRequest;
use App\Http\Requests\Installation\RunRequest;
use App\Http\Requests\Installation\StepRequest;
use App\Models\User;
use App\Support\Installation\Connections;
use App\Support\Installation\Environment;
use App\Support\Installation\Fields;
use App\Support\Installation\State;
use Closure;
use Database\Seeders\InstallationSeeder;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Rolix\Facades\Role as RoleService;
use JobMetric\Rolix\Models\Role;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Coordinate the resumable installation workflow.
 */
class Installation
{
    public const array TASKS = [
        'verify',
        'migrate',
        'seed',
        'administrator',
        'storage',
        'finish',
    ];

    /**
     * Initialize the service dependencies.
     *
     * @param Environment $environment
     * @param State $state
     * @param Connections $connections
     *
     * @return void
     */
    public function __construct(
        private readonly Environment $environment,
        private readonly State $state,
        private readonly Connections $connections
    ) {
    }

    /**
     * Determine if the installation has been completed.
     *
     * @return bool
     */
    public function completed(): bool
    {
        return config('installation.completed', false) || $this->environment->installed();
    }

    /**
     * Detect if an existing installation is present in the database.
     *
     * @return bool
     * @throws JsonException
     */
    public function detectExisting(): bool
    {
        if ($this->completed()) {
            return true;
        }

        $values = $this->environment->read();
        if (! empty($values['INSTALLATION_STATUS']) || $this->state->read() !== []) {
            return false;
        }

        try {
            $schema = DB::connection()->getSchemaBuilder();
            if ($schema->hasTable('migrations') && $schema->hasTable('users') && DB::table('users')->exists()) {
                $result = $this->state->locked(function (): bool {
                    if ($this->state->read() !== []) {
                        return false;
                    }

                    $this->environment->write([
                        'INSTALLATION'        => now()->toIso8601String(),
                        'INSTALLATION_STATUS' => 'completed',
                    ]);

                    config(['installation.completed' => true]);

                    return true;
                });

                return $result === true;
            }
        } catch (Throwable) {
            // A failed legacy connection is never treated as an empty database.
            // The explicit connection test checks emptiness before allowing installation.
        }

        return false;
    }

    /**
     * Check the system requirements for the installation.
     *
     * @return bool[]
     */
    public function requirements(): array
    {
        $key = (string) config('app.key');
        $decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $checks = [
            'php' => PHP_VERSION_ID >= 80300,
            'key' => is_string($decoded) && Encrypter::supported($decoded, config('app.cipher')),
        ];

        foreach (
            [
                'ctype',
                'curl',
                'dom',
                'fileinfo',
                'filter',
                'hash',
                'intl',
                'iconv',
                'json',
                'libxml',
                'mbstring',
                'openssl',
                'pcre',
                'PDO',
                'session',
                'tokenizer',
                'xml',
                'redis',
            ] as $extension) {
            $checks['extension_' . $extension] = extension_loaded($extension);
        }
        $checks['pdo_driver'] = extension_loaded('pdo_mysql') || extension_loaded('pdo_pgsql');
        $checks['env'] = is_writable($this->environment->path) && is_writable(dirname($this->environment->path));
        $checks['storage'] = collect([
            '',
            'framework',
            'framework/sessions',
            'framework/cache/data',
            'framework/views',
            'logs',
        ])->every(fn ($path) => is_dir(storage_path($path)) && is_writable(storage_path($path)));
        $checks['bootstrap_cache'] = is_writable(app()->bootstrapPath('cache'));
        $checks['assets'] = is_file(public_path('vuero/build/manifest.json'));

        return $checks;
    }

    /**
     * Get the current installation state and environment values for the view.
     *
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     * @throws JsonException
     */
    public function viewData(array $context): array
    {
        $state = $this->state->read();
        $owned = empty($state['owner']) || hash_equals($state['owner'], $this->owner($context));
        $environment = $owned ? $this->environment->read() : [];

        $values = [];
        foreach ([2, 5] as $step) {
            $values += Fields::values(Fields::fields($step), $environment);
        }

        if ($owned && isset($state['admin'])) {
            $values['ADMIN_NAME'] = $state['admin']['name'];
            $values['ADMIN_EMAIL'] = $state['admin']['email'];
        }

        // Secure defaults are shown for a genuinely new installation.
        if ((int) ($environment['INSTALLATION_STEP'] ?? 0) === 0) {
            $values['APP_ENV'] = 'production';
            $values['APP_DEBUG'] = '0';
        }

        if (empty($values['APP_URL']) || $values['APP_URL'] === 'http://localhost') {
            $values['APP_URL'] = $context['url'] ?? '';
        }

        return [
            'connectionTests' => [
                'database' => $owned && isset($state['database_test']) && hash_equals($state['database_test'], $this->fingerprint($this->credentials(array_intersect_key($values, Fields::groups(2)['database'])))),
            ],
            'values'          => $values,
            'requirements'    => $this->requirements(),
            'owned'           => $owned,
            'progress'        => $this->progress(),
            'tasks'           => self::TASKS,
        ];
    }

    /**
     * Get the current installation progress.
     *
     * @return array<array-key, mixed>
     */
    public function progress(): array
    {
        $values = $this->environment->read();
        $task = (int) ($values['INSTALLATION_TASK'] ?? 0);

        return [
            'step'      => (int) ($values['INSTALLATION_STEP'] ?? 0),
            'task'      => $task,
            'status'    => $values['INSTALLATION_STATUS'] ?? 'pending',
            'percent'   => (int) floor(min($task, count(self::TASKS)) / count(self::TASKS) * 100),
            'current'   => self::TASKS[$task] ?? null,
            'completed' => $this->completed(),
        ];
    }

    /**
     * Set the installation language and locale.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function language(array $data, array $context): Response
    {
        $validated = dto($data, LanguageRequest::class);

        return $this->mutate($context, function (array &$state) use ($validated): Response {
            $this->environment->write([
                'INSTALLATION_LOCALE' => $validated['locale'],
                'APP_LOCALE'          => $validated['locale'],
            ]);
            app()->setLocale($validated['locale']);

            return Response::make(true, trans('installation.saved'));
        });
    }

    /**
     * Test the database connection with the provided credentials.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function testDatabase(array $data, array $context): Response
    {
        $data = dto($data, DatabaseRequest::class);

        return $this->mutate($context, function (array &$state) use ($data): Response {
            $this->assertStep(2);
            $values = $this->credentials($data);
            unset($state['database_test']);
            $this->state->write($state);
            $this->connections->database($values);
            $state['database_test'] = $this->fingerprint($values);

            return Response::make(true, trans('installation.connection_ok'));
        }, 'database_failed');
    }

    /**
     * Validate and persist an essential installation step.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function save(array $data, array $context): Response
    {
        $data = dto($data, StepRequest::class);

        return $this->mutate($context, function (array &$state) use ($data): Response {
            $step = (int) $data['step'];
            $this->assertStep($step);
            $values = $this->credentials(array_intersect_key($data, Fields::fields($step)));
            if ($step === 1) {
                $values = ['APP_ENV' => 'production', 'APP_DEBUG' => false];
            }

            if ($step === 1 && in_array(false, $this->requirements(), true)) {
                return Response::make(false, trans('installation.requirements_failed'), null, 422);
            }

            if ($step === 2 && ($state['database_test'] ?? '') !== $this->fingerprint($values)) {
                return Response::make(false, trans('installation.test_required'), null, 422);
            }

            if ($step === 2) {
                $values['DB_URL'] = null;
            }

            if ($step === 5) {
                $state['admin'] = [
                    'name'     => $values['ADMIN_NAME'],
                    'email'    => strtolower($values['ADMIN_EMAIL']),
                    'password' => Hash::make($values['ADMIN_PASSWORD']),
                ];
                $values = array_intersect_key($values, array_flip([
                        'APP_NAME',
                        'APP_URL',
                    ])) + ['SESSION_SECURE_COOKIE' => str_starts_with($values['APP_URL'], 'https://')] + $this->defaults();
            } else {
                unset($state['admin']);
            }
            // State is committed before advancing the durable environment marker.
            $this->state->write($state);
            $this->environment->write($values + [
                    'INSTALLATION_STATUS' => 'configuring',
                    'INSTALLATION_STEP'   => $step,
                    'INSTALLATION_TASK'   => 0,
                ]);

            return Response::make(true, trans('installation.saved'), $this->progress());
        });
    }

    /**
     * Start the installation process.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function start(array $data, array $context): Response
    {
        dto($data, RunRequest::class);

        return $this->mutate($context, function (array &$state): Response {
            if ((int) ($this->environment->read()['INSTALLATION_STEP'] ?? 0) !== 5 || empty($state['admin'])) {
                return Response::make(false, trans('installation.order'), null, 422);
            }

            $this->environment->write([
                    'INSTALLATION_STATUS' => 'running',
                ] + $this->defaults());

            return Response::make(true, trans('installation.started'), $this->progress());
        });
    }

    /**
     * Advance to the next installation task.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     *
     * @return Response
     * @throws Throwable
     */
    public function next(array $data, array $context): Response
    {
        dto($data, RunRequest::class);

        return $this->mutate($context, function (array &$state): Response {
            $values = $this->environment->read();
            if (! in_array($values['INSTALLATION_STATUS'] ?? '', [
                    'running',
                    'failed',
                ], true) || empty($state['admin'])) {
                return Response::make(false, trans('installation.order'), null, 422);
            }

            $task = (int) ($values['INSTALLATION_TASK'] ?? 0);
            try {
                $this->execute($task, $state, $values);

                if ($task === count(self::TASKS) - 1) {
                    $this->environment->write([
                        'INSTALLATION'        => now()->toIso8601String(),
                        'INSTALLATION_STATUS' => 'completed',
                        'INSTALLATION_TASK'   => count(self::TASKS),
                    ]);
                    config([
                        'installation.completed' => true,
                    ]);
                    $state = [];
                    $this->state->clear();
                } else {
                    $this->environment->write([
                        'INSTALLATION_STATUS' => 'running',
                        'INSTALLATION_TASK'   => $task + 1,
                    ]);
                }

                return Response::make(true, trans('installation.task_done'), $this->progress());
            } catch (Throwable $exception) {
                // Record diagnostic identifiers only; exception messages may contain credentials or SQL bindings.
                Log::error('Installation task failed.', [
                    'task'           => self::TASKS[$task] ?? 'verify',
                    'exception_type' => $exception::class,
                    'error_code'     => (string) $exception->getCode(),
                ]);
                $this->environment->write([
                    'INSTALLATION_STATUS' => 'failed',
                ]);

                return Response::make(false, trans('installation.task_failed', [
                    'task' => trans('installation.tasks.' . (self::TASKS[$task] ?? 'verify')),
                ]), $this->progress(), 422);
            }
        });
    }

    /**
     * Release the installation ownership lock.
     *
     * @return Response
     * @throws Throwable
     */
    public function release(): Response
    {
        return $this->state->locked(function (): Response {
            $state = $this->state->read();
            unset($state['owner']);
            $this->state->write($state);

            return Response::make(true, 'Installation ownership released.');
        });
    }

    /**
     * Execute the installation task.
     *
     * @param int $task
     * @param array<string, mixed> $state
     * @param array<string, mixed> $values
     *
     * @return void
     * @throws Throwable
     */
    private function execute(int $task, array $state, array $values): void
    {
        $this->connections->configureDatabase($values);
        $previous = DB::getDefaultConnection();
        DB::setDefaultConnection('installation');

        try {
            switch ($task) {
                case 0:
                    if (in_array(false, $this->requirements(), true)) {
                        throw new RuntimeException('Requirements failed.');
                    }

                    $this->connections->database($values);

                    break;
                case 1:
                    if (Artisan::call('migrate', ['--database' => 'installation', '--force' => true]) !== 0) {
                        throw new RuntimeException('Migration failed.');
                    }

                    break;
                case 2:
                    if (Artisan::call('db:seed', [
                            '--database' => 'installation',
                            '--class'    => InstallationSeeder::class,
                            '--force'    => true,
                        ]) !== 0) {
                        throw new RuntimeException('Seeding failed.');
                    }

                    break;
                case 3:
                    $user = User::query()->where('email', $state['admin']['email'])->first();
                    if ($user && ! hash_equals($user->getRawOriginal('password'), $state['admin']['password'])) {
                        throw new RuntimeException('Existing account cannot be overwritten.');
                    }

                    if (! $user) {
                        $admin = $state['admin'];
                        $admin['metadata'] = [
                            'first_name' => $admin['name'],
                        ];

                        $user = User::query()->create($admin);
                    }

                    $superRole = Role::query()
                        ->where('type', 'system')
                        ->where('is_super', true)
                        ->first() ?? RoleService::store([
                        'type'        => 'system',
                        'name'        => 'System administrator',
                        'description' => 'Full CMS access for the installation administrator.',
                        'is_super'    => true,
                        'is_default'  => false,
                        'ordering'    => 0,
                        'allow'       => [],
                        'deny'        => [],
                        'rules'       => [],
                    ])->data;
                    $user->syncRoles([(int) $superRole->id]);

                    break;
                case 4:
                    $link = public_path('storage');
                    $target = config('filesystems.links')[$link] ?? storage_path('app/public');
                    $linked = fn (): bool => realpath($target) !== false && realpath($link) === realpath($target);

                    if (! $linked()) {
                        if (file_exists($link) || Artisan::call('storage:link') !== 0) {
                            throw new RuntimeException('Storage link could not be created.');
                        }

                        clearstatcache(true, $link);

                        if (! $linked()) {
                            throw new RuntimeException('Storage link could not be verified.');
                        }
                    }

                    break;
                case 5:
                    $this->environment->invalidateCache();

                    break;
                default:
                    throw new RuntimeException('Invalid installation task.');
            }
        } finally {
            DB::setDefaultConnection($previous);
            DB::purge('installation');
        }
    }

    /**
     * Mutate the installation state with a callback, handling ownership and errors.
     *
     * @param array<string, mixed> $context
     * @param Closure $callback
     * @param string $failure
     *
     * @return Response
     */
    private function mutate(array $context, Closure $callback, string $failure = 'save_failed'): Response
    {
        try {
            return $this->state->locked(function () use ($context, $callback): Response {
                if ($this->completed()) {
                    return Response::make(false, trans('installation.closed'), null, 403);
                }

                $state = $this->state->read();
                $owner = $this->owner($context);

                if (isset($state['owner']) && ! hash_equals($state['owner'], $owner)) {
                    return Response::make(false, trans('installation.owned'), null, 403);
                }

                $state['owner'] = $owner;
                $this->state->write($state);
                $result = $callback($state);

                if (! $this->completed()) {
                    $this->state->write($state);
                }

                return $result;
            });
        } catch (Throwable) {
            return Response::make(false, trans('installation.' . $failure), null, 422);
        }
    }

    /**
     * Supply supported first-login defaults without external services.
     *
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'APP_ENV'               => 'production',
            'APP_DEBUG'             => false,
            'CACHE_STORE'           => 'file',
            'QUEUE_CONNECTION'      => 'sync',
            'MAIL_MAILER'           => $this->environment->read()['MAIL_MAILER'] ?? 'log',
            'SESSION_DRIVER'        => 'file',
            'SESSION_CONNECTION'    => null,
            'SESSION_STORE'         => null,
            'SESSION_PATH'          => '/',
            'SESSION_DOMAIN'        => null,
            'SESSION_SAME_SITE'     => 'lax',
            'SESSION_SECURE_COOKIE' => str_starts_with($this->environment->read()['APP_URL'] ?? '', 'https://'),
            'SESSION_LIFETIME'      => 120,
            'SESSION_ENCRYPT'       => true,
            'LOG_CHANNEL'           => 'single',
            'LOG_LEVEL'             => 'error',
        ];
    }

    /**
     * Assert that the current installation step is valid.
     *
     * @param int $step
     *
     * @return void
     * @throws RuntimeException
     */
    private function assertStep(int $step): void
    {
        $values = $this->environment->read();

        if (! in_array($step, [
                1,
                2,
                5,
            ], true) || ($step === 5 ? (int) ($values['INSTALLATION_STEP'] ?? 0) < 2 : $step > (int) ($values['INSTALLATION_STEP'] ?? 0) + 1) || in_array($values['INSTALLATION_STATUS'] ?? '', [
                'running',
                'failed',
            ], true)) {
            throw new RuntimeException('Invalid step order.');
        }
    }

    /**
     * Get the hashed owner identifier from the context.
     *
     * @param array<string, mixed> $context
     *
     * @return string
     * @throws RuntimeException
     */
    private function owner(array $context): string
    {
        if (empty($context['owner'])) {
            throw new RuntimeException('Installer session is required.');
        }

        return hash('sha256', $context['owner']);
    }

    /**
     * Merge the provided credentials with existing environment values, preserving sensitive data.
     *
     * @param array<string, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private function credentials(array $values): array
    {
        $existing = $this->environment->read();
        foreach ($values as $key => &$value) {
            if (in_array($key, [
                    'DB_PASSWORD',
                    'REDIS_PASSWORD',
                    'MAIL_PASSWORD',
                ], true) && ($value === null || $value === '')) {
                $value = $existing[$key] ?? '';
            }

            if ($value === 'null' || $value === '(null)' || $value === null) {
                $value = '';
            }
        }

        return $values;
    }

    /**
     * Generate a fingerprint for the provided values to detect changes.
     *
     * @param array<string, mixed> $values
     *
     * @return string
     * @throws JsonException
     */
    private function fingerprint(array $values): string
    {
        ksort($values);

        return hash_hmac('sha256', json_encode(array_map(fn ($value
        ) => (string) $value, $values), JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
