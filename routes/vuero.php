<?php

use App\Http\Controllers\Vuero\Admin\DashboardController;
use App\Http\Controllers\Vuero\Admin\ProfileSettingsController;
use App\Http\Controllers\Vuero\Admin\StarterController;
use App\Http\Controllers\Vuero\Admin\WizardController;
use App\Http\Controllers\Vuero\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::prefix('vuero')
    ->name('vuero.')
    ->group(function (): void {
        Route::get('/', fn () => auth()->check()
            ? redirect()->route('vuero.admin.dashboard')
            : redirect()->route('vuero.login'))
            ->name('home');

        Route::middleware('guest')->group(function (): void {
            Route::get('/login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

            Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                ->name('login.store');
        });

        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->middleware('auth')
            ->name('logout');

        Route::middleware('auth')
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::get('/', DashboardController::class)->name('dashboard');
                Route::get('/wizard', WizardController::class)->name('wizard');
                Route::get('/starters/{starter}', StarterController::class)
                    ->where('starter', '(sidebar|sideblock|navbar)-blank-page-[1-8]')
                    ->name('starters.show');
                Route::get('/dashboards/{dashboard}', [DashboardController::class, 'show'])
                    ->where('dashboard', '[a-z0-9-]+')
                    ->name('dashboards.show');
                Route::get('/profile-settings', ProfileSettingsController::class)
                    ->name('profile-settings');
            });
    });
