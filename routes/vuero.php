<?php

use App\Http\Controllers\Vuero\Admin\DashboardController;
use App\Http\Controllers\Vuero\Admin\LayoutController;
use App\Http\Controllers\Vuero\Admin\ProfileLayoutController;
use App\Http\Controllers\Vuero\Admin\ProfileSettingsController;
use App\Http\Controllers\Vuero\Admin\StarterController;
use App\Http\Controllers\Vuero\Admin\WizardController;
use App\Http\Controllers\Vuero\ErrorPageController;
use App\Http\Controllers\Vuero\UtilityPageController;
use Illuminate\Support\Facades\Route;

Route::prefix('vuero')
    ->name('vuero.')
    ->group(function (): void {
        Route::get('/', fn () => auth()->check()
            ? redirect()->route('vuero.admin.dashboard')
            : redirect()->route('login'))
            ->name('home');

        Route::redirect('/login', '/login')->name('login');

        Route::middleware('auth')->group(function (): void {
            Route::get('/error/{page}', [ErrorPageController::class, 'show'])
                ->where('page', 'page-[1-5]')
                ->name('error.show');
            Route::get('/roadmap', [UtilityPageController::class, 'roadmap'])->name('utility.roadmap');
            Route::get('/status', [UtilityPageController::class, 'status'])->name('utility.status');
        });

        Route::middleware('auth')
            ->prefix('admin')
            ->name('admin.')
            ->group(function (): void {
                Route::get('/', DashboardController::class)->name('dashboard');
                Route::get('/wizard', WizardController::class)->name('wizard');
                Route::get('/layouts/profile-view', [ProfileLayoutController::class, 'view'])->name('layouts.profile-view');
                Route::get('/layouts/profile-notifications', [ProfileLayoutController::class, 'notifications'])->name('layouts.profile-notifications');
                Route::get('/layouts/profile-edit/{tab?}', [ProfileLayoutController::class, 'edit'])
                    ->where('tab', 'general|experience|skills|settings')
                    ->name('layouts.profile-edit');
                Route::get('/layouts/search-results', [ProfileLayoutController::class, 'searchResults'])->name('layouts.search-results');
                Route::get('/layouts/search-empty', [ProfileLayoutController::class, 'searchEmpty'])->name('layouts.search-empty');
                Route::get('/layouts/{layout}', [LayoutController::class, 'show'])
                    ->where('layout', 'list-view-v[1-4]|flex-list-v[1-3]|list-datatable-v[1-3]|form-layout-v[1-5]|placeload-v[1-4]|grid-card-v[1-4]|grid-tile-v[1-3]|user-grid-v[1-4]|projects-v[1-3]|project-details|kanban-board|onboarding-welcome|onboarding-v[1-5]|saas-billing|action-page-v[1-2]|utility-account-confirm|utility-promotion|utility-invoice')
                    ->name('layouts.show');
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
