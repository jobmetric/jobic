<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Languages\LanguageController;
use App\Http\Controllers\Menus\MenuController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Settings\DynamicSettingController;
use App\Http\Controllers\Settings\MailController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SystemController;
use App\Http\Controllers\Taxonomies\TaxonomyController;
use App\Http\Controllers\Users\UserController;
use App\Http\Middleware\Admin\EnsurePermission;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    Preferences::class,
])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->middleware(EnsurePermission::class.':admin.dashboard')->name('dashboard');

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

    Route::prefix('menus')->name('menus.')->controller(MenuController::class)->group(function (): void {
        Route::get('/', 'index')->middleware(EnsurePermission::class.':admin.menus.view')->name('index');
        Route::get('data', 'data')->middleware(EnsurePermission::class.':admin.menus.view')->name('data');
        Route::get('create', 'create')->middleware(EnsurePermission::class.':admin.menus.manage')->name('create');
        Route::post('/', 'store')->middleware([EnsurePermission::class.':admin.menus.manage', 'throttle:30,1'])->name('store');
        Route::post('bulk', 'bulk')->middleware([EnsurePermission::class.':admin.menus.manage', 'throttle:30,1'])->name('bulk');
        Route::get('{menu}/edit', 'edit')->whereNumber('menu')->middleware(EnsurePermission::class.':admin.menus.manage')->name('edit');
        Route::get('{menu}', 'show')->whereNumber('menu')->middleware(EnsurePermission::class.':admin.menus.view')->name('show');
        Route::put('{menu}', 'update')->whereNumber('menu')->middleware([EnsurePermission::class.':admin.menus.manage', 'throttle:30,1'])->name('update');
        Route::delete('{menu}', 'destroy')->whereNumber('menu')->middleware([EnsurePermission::class.':admin.menus.manage', 'throttle:30,1'])->name('destroy');
    });

    Route::prefix('taxonomies/{type}')->name('taxonomies.')->controller(TaxonomyController::class)->group(function (): void {
        Route::get('/', 'index')->middleware(EnsurePermission::class.':admin.content')->name('index');
        Route::get('link-fields/{driver}', 'linkFields')->middleware(EnsurePermission::class.':admin.content')->name('link-fields');
        Route::get('data', 'data')->middleware(EnsurePermission::class.':admin.content')->name('data');
        Route::get('create', 'create')->middleware(EnsurePermission::class.':admin.content')->name('create');
        Route::post('/', 'store')->middleware([EnsurePermission::class.':admin.content', 'throttle:30,1'])->name('store');
        Route::post('bulk', 'bulk')->middleware([EnsurePermission::class.':admin.content', 'throttle:30,1'])->name('bulk');
        Route::put('{taxonomy}/translations/{locale}', 'translation')->whereNumber('taxonomy')->middleware([EnsurePermission::class.':admin.content', 'throttle:30,1'])->name('translation');
        Route::get('{taxonomy}/history', 'history')->whereNumber('taxonomy')->middleware(EnsurePermission::class.':admin.content')->name('history');
        Route::get('{taxonomy}/edit', 'edit')->whereNumber('taxonomy')->middleware(EnsurePermission::class.':admin.content')->name('edit');
        Route::put('{taxonomy}', 'update')->whereNumber('taxonomy')->middleware([EnsurePermission::class.':admin.content', 'throttle:30,1'])->name('update');
        Route::delete('{taxonomy}', 'destroy')->whereNumber('taxonomy')->middleware([EnsurePermission::class.':admin.content', 'throttle:30,1'])->name('destroy');
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
