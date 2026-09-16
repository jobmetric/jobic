<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Settings\MailController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', Preferences::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/{section}', [SettingsController::class, 'edit'])->whereIn('section', ['general', 'profile', 'security', 'appearance'])->name('settings.edit');
        foreach (['general', 'profile', 'security', 'appearance'] as $section) {
            Route::put('/settings/'.$section, [SettingsController::class, $section])->middleware('throttle:30,1')->name('settings.'.$section.'.update');
        }
        Route::get('/settings/mail', [MailController::class, 'edit'])->name('settings.mail.edit');
        Route::put('/settings/mail', [MailController::class, 'update'])->name('settings.mail.update');

        Route::get('/', DashboardController::class)->name('dashboard');
    });
