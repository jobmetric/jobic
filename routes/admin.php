<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Languages\LanguageController;
use App\Http\Controllers\Media\MediaManagerController;
use App\Http\Controllers\Posts\PostController;
use App\Http\Controllers\Posts\PostLifecycleController;
use App\Http\Controllers\Posts\WorkflowController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Settings\DynamicSettingController;
use App\Http\Controllers\Settings\MailController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SystemController;
use App\Http\Controllers\Taxonomies\TaxonomyController;
use App\Http\Controllers\Users\UserController;
use App\Http\Middleware\Admin\EnsurePermission;
use App\Http\Middleware\Admin\EnsurePostPermission;
use App\Http\Middleware\Admin\EnsureTaxonomyPermission;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    Preferences::class,
])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->middleware(EnsurePermission::class.':admin.dashboard')->name('dashboard');

    Route::prefix('posts/{type}')->name('posts.')->controller(PostController::class)->middleware(EnsurePostPermission::class)->group(function (): void {
        Route::get('kanban', [\App\Http\Controllers\Posts\KanbanController::class, 'index'])->name('kanban.index');
        Route::get('kanban/default', [\App\Http\Controllers\Posts\KanbanController::class, 'index'])->name('kanban.default');
        Route::get('kanban/boards/create', [\App\Http\Controllers\Posts\ContentBoardController::class, 'form'])->name('kanban.boards.create');
        Route::get('kanban/boards/{board}/edit', [\App\Http\Controllers\Posts\ContentBoardController::class, 'form'])->whereNumber('board')->name('kanban.boards.edit');
        Route::get('kanban/boards/data', [\App\Http\Controllers\Posts\ContentBoardController::class, 'data'])->name('kanban.boards.data');
        Route::get('kanban/boards/manage', [\App\Http\Controllers\Posts\ContentBoardController::class, 'manage'])->name('kanban.boards.manage');
        Route::post('kanban/boards/bulk', [\App\Http\Controllers\Posts\ContentBoardController::class, 'bulk'])->middleware('throttle:30,1')->name('kanban.boards.bulk');
        Route::get('kanban/boards/{board}/history', [\App\Http\Controllers\Posts\ContentBoardController::class, 'history'])->whereNumber('board')->name('kanban.boards.history');
        Route::post('kanban/boards', [\App\Http\Controllers\Posts\ContentBoardController::class, 'save'])->middleware('throttle:30,1')->name('kanban.boards.store');
        Route::put('kanban/boards/{board}', [\App\Http\Controllers\Posts\ContentBoardController::class, 'save'])->whereNumber('board')->middleware('throttle:30,1')->name('kanban.boards.update');
        Route::delete('kanban/boards/{board}', [\App\Http\Controllers\Posts\ContentBoardController::class, 'destroy'])->whereNumber('board')->middleware('throttle:30,1')->name('kanban.boards.destroy');
        Route::get('kanban/boards/{board}', [\App\Http\Controllers\Posts\KanbanController::class, 'index'])->whereNumber('board')->name('kanban.board');
        Route::get('kanban/boards/{board}/data', [\App\Http\Controllers\Posts\KanbanController::class, 'data'])->whereNumber('board')->name('kanban.board.data');
        Route::get('kanban/boards/{board}/members', [\App\Http\Controllers\Posts\KanbanController::class, 'members'])->whereNumber('board')->name('kanban.board.members');
        Route::get('kanban/boards/{board}/calendar', [\App\Http\Controllers\Posts\KanbanController::class, 'calendar'])->whereNumber('board')->name('kanban.board.calendar');
        Route::get('kanban/boards/{board}/insights', [\App\Http\Controllers\Posts\KanbanController::class, 'insights'])->whereNumber('board')->name('kanban.board.insights');
        Route::get('kanban/boards/{board}/notifications', [\App\Http\Controllers\Posts\KanbanController::class, 'notifications'])->whereNumber('board')->name('kanban.board.notifications');
        Route::get('kanban/boards/{board}/posts/{post}', [\App\Http\Controllers\Posts\KanbanController::class, 'details'])->whereNumber(['board','post'])->name('kanban.board.details');
        Route::post('kanban/boards/{board}/command', [\App\Http\Controllers\Posts\KanbanController::class, 'command'])->whereNumber('board')->middleware('throttle:60,1')->name('kanban.board.command');
        Route::get('kanban/data', [\App\Http\Controllers\Posts\KanbanController::class, 'data'])->name('kanban.data');
        Route::get('kanban/members', [\App\Http\Controllers\Posts\KanbanController::class, 'members'])->name('kanban.members');
        Route::get('kanban/calendar', [\App\Http\Controllers\Posts\KanbanController::class, 'calendar'])->name('kanban.calendar');
        Route::get('kanban/insights', [\App\Http\Controllers\Posts\KanbanController::class, 'insights'])->name('kanban.insights');
        Route::get('kanban/notifications', [\App\Http\Controllers\Posts\KanbanController::class, 'notifications'])->name('kanban.notifications');
        Route::get('kanban/{post}', [\App\Http\Controllers\Posts\KanbanController::class, 'details'])->whereNumber('post')->name('kanban.details');
        Route::post('kanban/command', [\App\Http\Controllers\Posts\KanbanController::class, 'command'])->middleware('throttle:60,1')->name('kanban.command');
        Route::get('workflow', [WorkflowController::class, 'index'])->name('workflow.index');
        Route::post('workflow', [WorkflowController::class, 'save'])->middleware('throttle:30,1')->name('workflow.save');
        Route::post('{post}/lifecycle', [PostLifecycleController::class, 'command'])
            ->whereNumber('post')->middleware('throttle:30,1')->name('lifecycle.command');
        Route::get('/', 'index')->name('index');
        Route::get('data', 'data')->name('data');
        Route::get('create', 'create')->name('create');
        Route::post('editor-preview', 'editorPreview')->middleware('throttle:30,1')->name('editor-preview');
        Route::post('bulk', 'bulk')->middleware('throttle:30,1')->name('bulk');
        Route::post('edit-locks/unlock', 'unlockEditLocks')->middleware('throttle:30,1')->name('edit-locks.unlock');
        Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
        Route::get('{post}/translations/{locale}', 'translation')->whereNumber('post')->name('translation');
        Route::put('{post}/translations/{locale}', 'saveTranslation')->whereNumber('post')->middleware('throttle:30,1')->name('translation.update');
        Route::get('{post}/history', 'history')->whereNumber('post')->name('history');
        Route::get('{post}/edit', 'edit')->whereNumber('post')->name('edit');
        Route::post('{post}/edit-lock/unlock', 'unlockEditLock')->whereNumber('post')->middleware('throttle:30,1')->name('edit-lock.unlock');
        Route::post('{post}/edit-lock/renew', 'renewEditLock')->whereNumber('post')->middleware('throttle:60,1')->name('edit-lock.renew');
        Route::post('{post}/edit-lock/release', 'releaseEditLock')->whereNumber('post')->middleware('throttle:60,1')->name('edit-lock.release');
        Route::put('{post}', 'update')->whereNumber('post')->middleware('throttle:30,1')->name('update');
        Route::delete('{post}', 'destroy')->whereNumber('post')->middleware('throttle:30,1')->name('destroy');
        Route::post('{post}/revisions/{revision}/restore', 'restoreRevision')->whereNumber(['post', 'revision'])->middleware('throttle:30,1')->name('revisions.restore');
    });

    Route::prefix('media-manager')->name('media-manager.')->controller(MediaManagerController::class)->middleware(EnsurePermission::class.':admin.content,admin.content.taxonomy.manage.any,admin.content.posts.manage,admin.content.pages.manage')->group(function (): void {
        Route::get('browse', 'browse')->name('browse');
        Route::post('upload', 'command')->defaults('operation', 'upload')->middleware('throttle:120,1')->name('upload');
        Route::post('folders', 'command')->defaults('operation', 'folder')->middleware('throttle:30,1')->name('folders.store');
        Route::post('paste', 'command')->defaults('operation', 'paste')->name('paste');
        Route::post('compress', 'command')->defaults('operation', 'compress')->name('compress');
        Route::post('{media}/extract', 'command')->defaults('operation', 'extract')->whereNumber('media')->name('extract');
        Route::get('{media}/usages', 'usages')->whereNumber('media')->name('usages');
        Route::get('{media}/preview', 'preview')->whereNumber('media')->name('preview');
        Route::get('{media}/content', 'content')->whereNumber('media')->name('content');
        Route::get('{media}/details', 'details')->whereNumber('media')->name('details');
        Route::patch('{media}', 'command')->defaults('operation', 'rename')->whereNumber('media')->middleware('throttle:30,1')->name('rename');
        Route::delete('{media}', 'command')->defaults('operation', 'destroy')->whereNumber('media')->name('delete');
        Route::delete('/', 'command')->defaults('operation', 'destroy')->middleware('throttle:30,1')->name('destroy');
    });

    Route::prefix('users')->name('users.')->controller(UserController::class)->group(function (): void {
        Route::get('/', 'index')->middleware(EnsurePermission::class.':admin.users.view')->name('index');
        Route::get('data', 'data')->middleware(EnsurePermission::class.':admin.users.view')->name('data');
        Route::get('create', 'create')->middleware(EnsurePermission::class.':admin.users.manage')->name('create');
        Route::post('/', 'store')->middleware([EnsurePermission::class.':admin.users.manage', 'throttle:30,1'])->name('store');
        Route::post('bulk', 'bulk')->middleware([EnsurePermission::class.':admin.users.manage', 'throttle:30,1'])->name('bulk');
        Route::get('{user}/history', 'history')->whereNumber('user')->middleware(EnsurePermission::class.':admin.users.view')->name('history');
        Route::get('{user}/edit', 'edit')->whereNumber('user')->middleware(EnsurePermission::class.':admin.users.manage')->name('edit');
        Route::get('{user}', 'show')->whereNumber('user')->middleware(EnsurePermission::class.':admin.users.view')->name('show');
        Route::put('{user}', 'update')->whereNumber('user')->middleware([EnsurePermission::class.':admin.users.manage', 'throttle:30,1'])->name('update');
    });

    Route::prefix('roles')->name('roles.')->controller(RoleController::class)->group(function (): void {
        Route::get('/', 'index')->middleware(EnsurePermission::class.':admin.roles.view')->name('index');
        Route::get('data', 'data')->middleware(EnsurePermission::class.':admin.roles.view')->name('data');
        Route::get('create', 'create')->middleware(EnsurePermission::class.':admin.roles.manage')->name('create');
        Route::post('/', 'store')->middleware([EnsurePermission::class.':admin.roles.manage', 'throttle:30,1'])->name('store');
        Route::post('bulk', 'bulk')->middleware([EnsurePermission::class.':admin.roles.manage', 'throttle:30,1'])->name('bulk');
        Route::get('{role}/history', 'history')->whereNumber('role')->middleware(EnsurePermission::class.':admin.roles.view')->name('history');
        Route::get('{role}/edit', 'edit')->whereNumber('role')->middleware(EnsurePermission::class.':admin.roles.manage')->name('edit');
        Route::get('{role}', 'show')->whereNumber('role')->middleware(EnsurePermission::class.':admin.roles.view')->name('show');
        Route::put('{role}', 'update')->whereNumber('role')->middleware([EnsurePermission::class.':admin.roles.manage', 'throttle:30,1'])->name('update');
    });

    Route::prefix('taxonomies/{type}')->name('taxonomies.')->controller(TaxonomyController::class)->group(function (): void {
        Route::get('/', 'index')->middleware(EnsureTaxonomyPermission::class)->name('index');
        Route::get('drivers/{driver}/fields', 'driverFields')->middleware(EnsureTaxonomyPermission::class)->name('driver-fields');
        Route::get('data', 'data')->middleware(EnsureTaxonomyPermission::class)->name('data');
        Route::get('create', 'create')->middleware(EnsureTaxonomyPermission::class)->name('create');
        Route::post('/', 'store')->middleware([EnsureTaxonomyPermission::class, 'throttle:30,1'])->name('store');
        Route::post('edit-locks/unlock', 'unlockEditLocks')->middleware([EnsureTaxonomyPermission::class, 'throttle:30,1'])->name('edit-locks.unlock');
        Route::post('bulk', 'bulk')->middleware([EnsureTaxonomyPermission::class, 'throttle:30,1'])->name('bulk');
        Route::put('{taxonomy}/translations/{locale}', 'translation')->whereNumber('taxonomy')->middleware([EnsureTaxonomyPermission::class, 'throttle:30,1'])->name('translation');
        Route::get('{taxonomy}/history', 'history')->whereNumber('taxonomy')->middleware(EnsureTaxonomyPermission::class)->name('history');
        Route::get('{taxonomy}/edit', 'edit')->whereNumber('taxonomy')->middleware(EnsureTaxonomyPermission::class)->name('edit');
        Route::post('{taxonomy}/edit-lock/renew', 'renewEditLock')->whereNumber('taxonomy')->middleware(EnsureTaxonomyPermission::class)->name('edit-lock.renew');
        Route::post('{taxonomy}/edit-lock/release', 'releaseEditLock')->whereNumber('taxonomy')->middleware(EnsureTaxonomyPermission::class)->name('edit-lock.release');
        Route::post('{taxonomy}/edit-lock/unlock', 'unlockEditLock')->whereNumber('taxonomy')->middleware(EnsureTaxonomyPermission::class)->name('edit-lock.unlock');
        Route::put('{taxonomy}', 'update')->whereNumber('taxonomy')->middleware([EnsureTaxonomyPermission::class, 'throttle:30,1'])->name('update');
        Route::delete('{taxonomy}', 'destroy')->whereNumber('taxonomy')->middleware([EnsureTaxonomyPermission::class, 'throttle:30,1'])->name('destroy');
    });

    Route::prefix('account')->name('account.')->controller(SettingsController::class)->group(function (): void {
        Route::get('profile', 'accountProfile')->name('profile.edit');
        Route::put('profile', 'profile')->middleware('throttle:30,1')->name('profile.update');
        Route::put('profile/avatar', 'avatar')->middleware('throttle:30,1')->name('profile.avatar');
        Route::get('security', 'accountSecurity')->name('security.edit');
        Route::put('security', 'security')->middleware('throttle:30,1')->name('security.update');
        Route::post('security/two-factor', 'twoFactor')->middleware('throttle:5,1')->name('security.two-factor');
        Route::get('appearance', 'accountAppearance')->name('appearance.edit');
        Route::put('appearance', 'appearance')->middleware('throttle:30,1')->name('appearance.update');
    });

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::prefix('extensions')->name('extensions.')->controller(\App\Http\Controllers\Extensions\ExtensionController::class)->group(function (): void {
            Route::get('/', 'index')->middleware(EnsurePermission::class.':admin.settings.extensions.view')->name('index');
            Route::get('data', 'data')->middleware(EnsurePermission::class.':admin.settings.extensions.view')->name('data');
            Route::get('create', 'create')->middleware(EnsurePermission::class.':admin.settings.extensions.upload')->name('create');
            Route::get('examples/{variant}', 'exampleArchive')->whereIn('variant', array_keys(config('extensions.examples', [])))->middleware(EnsurePermission::class.':admin.settings.extensions.upload')->name('example');
            Route::get('upgrade', 'create')->middleware(EnsurePermission::class.':admin.settings.extensions.upgrade')->name('upgrade-form');
            foreach (['activate', 'deactivate', 'delete'] as $operation) {
                $permissions = [EnsurePermission::class.':admin.settings.extensions.'.$operation, 'throttle:20,1'];
                if ($operation === 'delete') {
                    $permissions[] = EnsurePermission::class.':admin.settings.extensions.uninstall';
                }
                Route::post('bulk/'.$operation, 'bulkExtensions')->defaults('operation', $operation)->middleware($permissions)->name('bulk.'.$operation);
            }
            foreach (['upload', 'install', 'activate', 'deactivate', 'upgrade', 'uninstall', 'delete'] as $operation) {
                Route::post('commands/'.$operation, 'command')->defaults('operation', $operation)
                    ->middleware([EnsurePermission::class.':admin.settings.extensions.'.$operation, 'throttle:20,1'])->name($operation);
            }
            foreach (['upload', 'upgrade'] as $operation) {
                Route::post('commands/'.$operation.'/discard', 'discardArchive')->defaults('operation', $operation)
                    ->middleware([EnsurePermission::class.':admin.settings.extensions.'.$operation, 'throttle:30,1'])->name($operation.'-discard');
                Route::post('commands/'.$operation.'/confirm', 'confirmArchive')->defaults('operation', $operation)
                    ->middleware([EnsurePermission::class.':admin.settings.extensions.'.$operation, 'throttle:20,1'])->name($operation.'-confirm');
            }
            Route::middleware(EnsurePermission::class.':admin.settings.extensions.plugins')->group(function (): void {
                Route::get('{extension}/plugins', 'pluginIndex')->whereNumber('extension')->name('plugins');
                Route::get('{extension}/plugins/data', 'pluginData')->whereNumber('extension')->name('plugins.data');
                Route::post('{extension}/plugins/bulk', 'bulkPlugins')->whereNumber('extension')->middleware('throttle:30,1')->name('plugins.bulk');
                Route::get('{extension}/plugins/create', 'plugins')->whereNumber('extension')->name('plugins.create');
                Route::get('{extension}/plugins/{plugin}/history', 'pluginHistory')->whereNumber(['extension', 'plugin'])->name('plugins.history');
                Route::post('{extension}/plugins/{plugin}/commands/{operation}', 'pluginCommand')->whereNumber(['extension', 'plugin'])->whereIn('operation', ['activate', 'deactivate', 'delete'])->middleware('throttle:30,1')->name('plugins.command');
                Route::get('{extension}/plugins/{plugin}/edit', 'plugins')->whereNumber(['extension', 'plugin'])->name('plugins.edit');
                Route::post('{extension}/plugins', 'savePlugin')->whereNumber('extension')->middleware('throttle:30,1')->name('plugins.store');
                Route::post('{extension}/plugins/{plugin}', 'savePlugin')->whereNumber(['extension', 'plugin'])->middleware('throttle:30,1')->name('plugins.update');
                Route::delete('{extension}/plugins/{plugin}', 'deletePlugin')->whereNumber(['extension', 'plugin'])->middleware('throttle:30,1')->name('plugins.delete');
            });
        });

        Route::prefix('forms/{application}/{key}')->name('forms.')->controller(DynamicSettingController::class)->group(function (): void {
            Route::get('/', 'edit')->middleware(EnsurePermission::class.':admin.settings.dynamic')->name('edit');
            Route::put('/', 'update')->middleware([EnsurePermission::class.':admin.settings.dynamic', 'throttle:30,1'])->name('update');
        });
        Route::controller(SettingsController::class)->group(function () {
            Route::get('/', 'index')->middleware(EnsurePermission::class.':admin.settings.any')->name('index');

            $settingPages = [
                'general',
                'seo',
            ];
            Route::get('{section}', 'edit')->whereIn('section', $settingPages)->middleware(EnsurePermission::class.':admin.settings.section')->name('edit');

            foreach ($settingPages as $section) {
                $middleware = ['throttle:30,1'];
                array_unshift($middleware, EnsurePermission::class.':admin.settings.'.$section);
                Route::put($section, $section)->middleware($middleware)->name($section.'.update');
            }
        });

        Route::controller(SystemController::class)->group(function () {
            Route::get('health', 'health')->middleware(EnsurePermission::class.':admin.settings.health')->name('health');

            Route::prefix('cache')->name('cache.')->group(function () {
                Route::get('/', 'edit')->middleware(EnsurePermission::class.':admin.settings.cache')->name('edit');
                Route::put('/', 'update')->middleware([EnsurePermission::class.':admin.settings.cache', 'throttle:30,1'])->name('update');
                Route::post('test', 'update')->middleware([EnsurePermission::class.':admin.settings.cache', 'throttle:6,1'])->name('test');
            });
        });

        Route::prefix('mail')->controller(MailController::class)->name('mail.')->group(function () {
            Route::get('/', 'edit')->middleware(EnsurePermission::class.':admin.settings.mail')->name('edit');
            Route::put('/', 'update')->middleware([EnsurePermission::class.':admin.settings.mail', 'throttle:30,1'])->name('update');
            Route::post('test', 'test')->middleware([EnsurePermission::class.':admin.settings.mail', 'throttle:6,1'])->name('test');
        });

        Route::prefix('languages')
            ->name('languages.')
            ->controller(LanguageController::class)
            ->middleware(EnsurePermission::class.':admin.settings.languages')
            ->group(function (): void {
                Route::get('/', 'index')->name('index');
                Route::get('data', 'data')->name('data');
                Route::get('create', 'create')->name('create');
                Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
                Route::post('bulk', 'bulk')->middleware('throttle:30,1')->name('bulk');
                Route::get('{language}/history', 'history')->whereNumber('language')->name('history');
                Route::get('{language}/edit', 'edit')->whereNumber('language')->name('edit');
                Route::get('{language}', 'show')->whereNumber('language')->name('show');
                Route::put('{language}', 'update')->whereNumber('language')->middleware('throttle:30,1')->name('update');
            });
    });
});
