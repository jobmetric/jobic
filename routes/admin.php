<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Languages\LanguageController;
use App\Http\Controllers\Settings\MailController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\SystemController;
use App\Http\Controllers\Users\UserController;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    Preferences::class,
])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::prefix('users')->name('users.')->controller(UserController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::get('data', 'data')->name('data');
        Route::get('create', 'create')->name('create');
        Route::post('/', 'store')->middleware('throttle:30,1')->name('store');
        Route::post('bulk', 'bulk')->middleware('throttle:30,1')->name('bulk');
        Route::get('{user}/history', 'history')->whereNumber('user')->name('history');
        Route::get('{user}/edit', 'edit')->whereNumber('user')->name('edit');
        Route::get('{user}', 'show')->whereNumber('user')->name('show');
        Route::put('{user}', 'update')->whereNumber('user')->middleware('throttle:30,1')->name('update');
    });

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::controller(SettingsController::class)->group(function () {
            Route::post('security/two-factor', 'twoFactor')->middleware('throttle:5,1')->name('security.two-factor');
            Route::put('profile/avatar', 'avatar')->middleware('throttle:30,1')->name('profile.avatar');
            Route::get('/', 'index')->name('index');

            $settingPages = [
                'general',
                'profile',
                'security',
                'appearance',
            ];
            Route::get('{section}', 'edit')->whereIn('section', $settingPages)->name('edit');

            foreach ($settingPages as $section) {
                Route::put($section, $section)->middleware('throttle:30,1')->name($section.'.update');
            }
        });

        Route::controller(SystemController::class)->group(function () {
            Route::get('health', 'health')->name('health');

            Route::prefix('cache')->name('cache.')->group(function () {
                Route::get('/', 'edit')->name('edit');
                Route::put('/', 'update')->middleware('throttle:30,1')->name('update');
                Route::post('test', 'update')->middleware('throttle:6,1')->name('test');
            });
        });

        Route::prefix('mail')->controller(MailController::class)->name('mail.')->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->middleware('throttle:30,1')->name('update');
            Route::post('test', 'test')->middleware('throttle:6,1')->name('test');
        });

        Route::prefix('languages')
            ->name('languages.')
            ->controller(LanguageController::class)
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
