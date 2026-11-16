<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('posts:run-schedules')->everyMinute()->withoutOverlapping();
Schedule::command('kanban:send-reminders')->hourly()->withoutOverlapping();

Artisan::command('kanban:send-reminders', function (): void {
    $this->info((string) app(\App\Services\Posts\KanbanNotifications::class)->sendDeadlineReminders());
})->purpose('Notify assigned board members about upcoming editorial deadlines.');


Artisan::command('extensions:disable {namespace?} {--all}', function (): void {
    $runtime = app(\App\Support\Extensions\Runtime::class);
    $runtime->locked(function () use ($runtime): void {
        $namespaces = $this->option('all')
            ? \JobMetric\Extension\Models\Extension::pluck('namespace')->all()
            : array_filter([$this->argument('namespace')]);
        foreach ($namespaces as $namespace) {
            $runtime->set($namespace, false);
        }
        $this->info('Extension loading disabled. Data was preserved.');
    });
})->purpose('Recover administration access by disabling extension loading without deleting data.');

Artisan::command('extensions:migrate {namespace}', function (): int {
    $namespace = $this->argument('namespace');
    if (! app(\App\Support\Extensions\Runtime::class)->recovering()) {
        $this->error('Safe mode is required for the extension upgrade worker.');
        return 1;
    }
    if (! \JobMetric\Extension\Models\Extension::whereNamespace($namespace)->exists()) {
        $this->error('The selected extension is not installed.');
        return 1;
    }
    $prefix = trim(app()->getNamespace(), '\\').'\\Extensions\\';
    if (str_starts_with($namespace, $prefix)) {
        $root = (string) config('extensions.root');
        $path = $root.'/'.str_replace('\\', '/', substr($namespace, strlen($prefix))).'.php';
        $canonicalRoot = str_replace('\\', '/', (string) realpath($root));
        $canonicalPath = str_replace('\\', '/', (string) realpath($path));
        if ($canonicalRoot === '' || ! str_starts_with($canonicalPath, $canonicalRoot.'/')) {
            $this->error('The managed extension entry file is unavailable.');
            return 1;
        }
        foreach (\Composer\Autoload\ClassLoader::getRegisteredLoaders() as $loader) {
            $loader->addPsr4($prefix, $root, true);
            $loader->addClassMap([$namespace => $path]);
        }
    }
    if (! is_subclass_of($namespace, \JobMetric\Extension\Contracts\AbstractExtension::class)) {
        $this->error('The entry class does not implement the extension contract.');
        return 1;
    }
    if (method_exists(app('Extension'), 'upgradeComposition')) {
        app('Extension')->upgradeComposition($namespace);
    } else {
        $manifest = json_decode(file_get_contents(dirname((new \ReflectionClass($namespace))->getFileName()).'/extension.json'), true);
        if (! empty($manifest['children'])) {
            $this->error('The installed package does not support composed upgrades.');
            return 1;
        }
        app($namespace)->upgrade();
    }

    return 0;
})->purpose('Run an installed extension upgrade in an isolated safe-mode process.');

Artisan::command('extensions:purge-staging', function (): void {
    $this->info((string) app(\App\Services\Extensions\Extensions::class)->purgeArchives()->data['removed']);
})->purpose('Remove expired private extension archive reviews.');
Schedule::command('extensions:purge-staging')->everyMinute()->withoutOverlapping();
