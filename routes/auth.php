<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\LegalController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::controller(AuthenticatedSessionController::class)->group(function () {
        Route::prefix('login')->group(function () {
            Route::get('two-factor', 'twoFactor')->name('login.two-factor');
            Route::post('two-factor', 'challenge')->middleware('throttle:10,1')->name('login.two-factor.store');
            Route::get('/', 'create')->name('login');
            Route::post('/', 'store')->name('login.store');
            Route::post('language', [AuthenticatedSessionController::class, 'language'])->middleware('throttle:30,1')->name('login.language');
        });
    });

    Route::name('password.')->group(function () {
        Route::prefix('forgot-password')->controller(PasswordResetLinkController::class)->group(function () {
            Route::get('/', 'create')->name('request');
            Route::post('/', 'store')->name('email');
        });

        Route::prefix('reset-password')->controller(NewPasswordController::class)->group(function () {
            Route::get('{token}', 'create')->name('reset');
            Route::post('/', 'store')->name('update');
        });
    });
});

Route::controller(AuthenticatedSessionController::class)->group(function () {
    Route::post('logout', 'destroy')->middleware('auth')->name('logout');

    Route::post('legal/language', [AuthenticatedSessionController::class, 'language'])->middleware('throttle:30,1')->name('legal.language');
});

Route::name('legal.')->controller(LegalController::class)->group(function () {
    Route::get('privacy', 'privacy')->name('privacy');
    Route::get('cookies', 'cookies')->name('cookies');
});
