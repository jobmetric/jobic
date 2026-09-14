<?php

// Isolated browser fixture. Never mount this router in production.
declare(strict_types=1);

use App\Models\User;
use App\Services\Installation\Installation;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Bootstrap\RegisterProviders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$root = dirname(__DIR__, 2);
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_starts_with($path, '/vuero/') && is_file($root.'/public'.$path)) {
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
        return ['php' => true, 'key' => true, 'env' => true, 'storage' => true, 'assets' => true];
    }
}

$app->afterBootstrapping(BootProviders::class, function (): void {
    if (str_starts_with(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/admin')) {
        config(['installation.completed' => true]);
        $user = new User(['name' => 'Layout Test Admin', 'email' => 'layout@example.test']);
        $user->id = 1;
        Auth::guard('web')->setUser($user);
    }
});

$app->handleRequest(Request::capture());
