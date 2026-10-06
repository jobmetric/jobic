<?php
// Test-only router: all writes are confined to the supplied temporary sandbox.
$root=dirname(__DIR__,2);
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if (is_file($root.'/public'.$path) && !str_ends_with($path,'.php')) return false;
$directory=getenv('JOBIC_MEDIA_SANDBOX');
if (!$directory || !is_dir($directory)) { http_response_code(500); exit; }
$loader=require $root.'/vendor/autoload.php';
if (getenv('JOBIC_MEDIA_SOURCE')) $loader->addPsr4('JobMetric\\Media\\',dirname($root).'/maral/packages/media/src/',true);
$app=require $root.'/bootstrap/app.php';
$app->useEnvironmentPath($directory);
$app->useStoragePath($directory.'/storage');
$app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class,function() use($directory) {
    config(['app.key'=>'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'app.url'=>'http://127.0.0.1:8897', 'app.debug'=>true,
        'database.default'=>'sqlite','database.connections.sqlite.database'=>$directory.'/database.sqlite','database.connections.sqlite.url'=>null,
        'cache.default'=>'array','session.driver'=>'file','session.secure'=>false,'session.domain'=>null,'installation.completed'=>true,
        'filesystems.disks.media_public.root'=>$directory.'/media','filesystems.disks.media_public.driver'=>'local']);
});
$app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\BootProviders::class,function() use($directory) {
    config(['filesystems.disks.media_public.root'=>$directory.'/media','filesystems.disks.media_public.driver'=>'local']);
    if(!is_file($directory.'/ready')) {
        \Illuminate\Support\Facades\Artisan::call('migrate',['--force'=>true]);
        $user=\App\Models\User::factory()->create(['email'=>'media-fixture@example.test','ui_preferences'=>['locale'=>getenv('JOBIC_MEDIA_LOCALE') ?: 'fa']]);
        $role=\JobMetric\Rolix\Models\Role::query()->create(['type'=>'system','name'=>'Fixture administrator','is_super'=>true,'is_default'=>false,'ordering'=>0]);
        $user->assignRole($role->id);
        file_put_contents($directory.'/ready','1');
    }
    \Illuminate\Support\Facades\Auth::guard('web')->setUser(\App\Models\User::where('email','media-fixture@example.test')->firstOrFail());
    \JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::register('media_fixture')->label('آزمایش رسانه')->url()->urlPrefix('catalog/media')->media([['collection'=>'base','multiple'=>true,'mimeTypes'=>['image']]]);
});
$app->handleRequest(\Illuminate\Http\Request::capture());
