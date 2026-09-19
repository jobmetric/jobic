<?php

// Isolated browser fixture. Never mount this router in production.
declare(strict_types=1);

use App\Models\User;
use App\Services\Installation\Installation;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ((str_starts_with($path, '/vuero/') || str_starts_with($path, '/fonts/') || str_starts_with($path, '/images/') || str_starts_with($path, '/assets/')) && is_file($root.'/public'.$path)) {
    return false;
}
require $root.'/vendor/autoload.php';
$directory = getenv('JOBIC_BROWSER_SANDBOX');
if (! $directory || ! is_dir($directory)) {
    http_response_code(500);
    exit('JOBIC_BROWSER_SANDBOX is required.');
}
putenv('APP_CONFIG_CACHE='.$directory.'/config.php');
$app = require $root.'/bootstrap/app.php';
$app->useEnvironmentPath($directory);
$app->useStoragePath($directory.'/storage');
$app->afterBootstrapping(RegisterProviders::class, function ($app): void {
    // Network adapters are covered by backend tests; browser tests focus on the UI.
    $app->singleton(Installation::class, BrowserInstallation::class);
});
class BrowserInstallation extends Installation
{
    public function requirements(): array
    {
        return array_fill_keys(['php', 'key', 'pdo_driver', 'env', 'storage', 'bootstrap_cache', 'assets',
            'extension_ctype', 'extension_curl', 'extension_dom', 'extension_fileinfo', 'extension_filter',
            'extension_hash', 'extension_mbstring', 'extension_openssl', 'extension_pcre', 'extension_PDO',
            'extension_session', 'extension_tokenizer', 'extension_xml', 'extension_redis'], true);
    }
}

$app->afterBootstrapping(BootProviders::class, function (): void {
    if (str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/admin')) {
        config(['installation.completed' => true]);
        $database = getenv('JOBIC_BROWSER_SANDBOX').'/browser.sqlite';
        $fresh = ! is_file($database);
        if ($fresh) {
            touch($database);
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database, 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        if ($fresh) {
            Artisan::call('migrate', ['--force' => true]);
        }
        $user = User::firstOrCreate(['email' => 'layout@example.test'], ['name' => 'Layout Test Admin', 'password' => 'fixture-password-123']);
        Auth::guard('web')->setUser($user);
    }
});

$app->handleRequest(Request::capture());
