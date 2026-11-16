<?php

// Isolated extension browser fixture. Never mount this router in production.
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('~^/(vuero|assets|images|fonts)/~', $path) && is_file($root.'/public'.$path)) {
    return false;
}
$directory = getenv('JOBIC_EXTENSIONS_SANDBOX');
if (! $directory || ! is_dir($directory) || ! str_starts_with(basename($directory), 'jobic-extensions-')) {
    http_response_code(500);
    exit('An isolated extensions sandbox is required.');
}
// Resolve only test-owned extension files from the private sandbox. Other namespaces
// use Composer's registered PSR-4 paths, matching the package helper's contract.
function resolveNamespacePath(string $namespace): ?string
{
    $namespace = trim($namespace, '\\');
    if ($namespace === 'App\\Extensions' || str_starts_with($namespace, 'App\\Extensions\\')) {
        return getenv('JOBIC_EXTENSIONS_SANDBOX').'/owned/'.str_replace('\\', '/', ltrim(substr($namespace, strlen('App\\Extensions')), '\\'));
    }
    foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
        $mappings = $loader->getPrefixesPsr4();
        uksort($mappings, static fn ($a, $b) => strlen($b) <=> strlen($a));
        foreach ($mappings as $prefix => $paths) {
            if ($namespace !== rtrim($prefix, '\\') && ! str_starts_with($namespace, $prefix)) { continue; }
            foreach ($paths as $path) {
                $candidate = rtrim($path, '/\\').'/'.str_replace('\\', '/', ltrim(substr($namespace, strlen(rtrim($prefix, '\\'))), '\\'));
                if (is_dir($candidate) || is_file($candidate.'.php')) { return $candidate; }
            }
        }
    }
    return null;
}

require $root.'/tests/Support/extension-package-bootstrap.php';
$app = require $root.'/bootstrap/app.php';
$app->useEnvironmentPath($directory);
$app->useStoragePath($directory.'/storage');
$app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function () use ($directory): void {
    config([
        'app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'app.url' => getenv('JOBIC_EXTENSIONS_BASE_URL') ?: 'http://127.0.0.1:8913', 'app.debug' => true,
        'app.asset_url' => getenv('JOBIC_EXTENSIONS_ASSET_URL') ?: null,
        'database.default' => 'sqlite', 'database.connections.sqlite.database' => $directory.'/database.sqlite', 'database.connections.sqlite.url' => null,
        'cache.default' => 'array', 'session.driver' => 'file', 'session.secure' => false, 'session.domain' => null,
        'installation.completed' => true, 'extensions.runtime_path' => $directory.'/extensions',
        'extensions.root' => $directory.'/owned', 'extensions.safe_mode' => false,
    ]);
});
$app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\RegisterProviders::class, function () use ($directory, $loader): void {
    // Test-only source injection applies equally to HTTP and the real CLI upgrade worker.
    // Deployment code continues to resolve only published Composer releases.
    if (getenv('JOBIC_EXTENSION_PACKAGE_SOURCE')) {
        app()->singleton(\App\Support\Extensions\Runtime::class, function () {
            return new class extends \App\Support\Extensions\Runtime {
                public function workerEnvironment(): array
                {
                    return parent::workerEnvironment() + ['JOBIC_EXTENSION_PACKAGE_SOURCE' => getenv('JOBIC_EXTENSION_PACKAGE_SOURCE')];
                }
                public function upgradeCommand(string $namespace): array
                {
                    $command = parent::upgradeCommand($namespace);
                    array_splice($command, 1, 0, ['-d', 'auto_prepend_file='.base_path('tests/Support/extension-package-bootstrap.php')]);
                    return $command;
                }
            };
        });
    }
    $loader->addPsr4('App\\Extensions\\', $directory.'/owned', true);
    \JobMetric\Extension\Facades\ExtensionTypeRegistry::register('PaymentMethod', ['label' => 'PaymentMethod']);
    \JobMetric\Extension\Facades\ExtensionNamespaceRegistry::register('Tests\\Fixtures\\Extensions');
});
$app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\BootProviders::class, function () use ($directory): void {
    if (! is_file($directory.'/ready')) {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => \Database\Seeders\InstallationSeeder::class, '--force' => true]);
        $user = \App\Models\User::factory()->create(['email' => 'extensions@example.test']);
        $role = \JobMetric\Rolix\Models\Role::firstOrCreate(['type' => 'system', 'is_super' => true], ['name' => 'Extensions fixture', 'is_default' => false, 'ordering' => 0]);
        $user->assignRole($role->id);
        file_put_contents($directory.'/ready', '1');
    }
    $user = \App\Models\User::where('email', 'extensions@example.test')->firstOrFail();
    $user->ui_preferences = ['locale' => getenv('JOBIC_EXTENSIONS_LOCALE') ?: 'en'];
    \Illuminate\Support\Facades\Auth::guard('web')->setUser($user);
    if (! is_file($directory.'/config.php')) {
        file_put_contents($directory.'/config.php', '<?php return '.var_export(config()->all(), true).';');
    }
});
$request = \Illuminate\Http\Request::capture();
$upload = $request->file('archive');
$uploadPath = $upload ? ($upload->getRealPath() ?: $upload->getPathname()) : null;
if ($uploadPath) {
    $nativeUpload = is_uploaded_file($uploadPath);
    $app->terminating(function () use ($uploadPath, $nativeUpload, $directory): void {
        // This runs before PHP's request-shutdown cleanup, proving the domain removed the native ZIP.
        file_put_contents($directory.'/upload-cleanup.json', json_encode(['native' => $nativeUpload, 'exists_after_processing' => is_file($uploadPath)]));
    });
}
$app->handleRequest($request);
