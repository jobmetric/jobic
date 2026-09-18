<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Languages\LanguageController;
use App\Http\Controllers\Settings\MailController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware([
    'auth',
    Preferences::class,
])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::prefix('settings')->name('settings.')->group(function (): void {
        Route::controller(SettingsController::class)->group(function () {
            Route::get('/', 'index')->name('index');

            $settingPages = [
                'general',
                'profile',
                'security',
                'appearance',
            ];
            Route::get('{section}', 'edit')->whereIn('section', $settingPages)->name('edit');

            foreach ($settingPages as $section) {
                Route::put($section, $section)->middleware('throttle:30,1')->name($section . '.update');
            }
        });

        Route::prefix('mail')->controller(MailController::class)->name('mail.')->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::put('/', 'update')->name('update');
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
