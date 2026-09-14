<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Settings\MailController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/settings/mail', [MailController::class, 'edit'])->name('settings.mail.edit');
        Route::put('/settings/mail', [MailController::class, 'update'])->name('settings.mail.update');

        Route::get('/', DashboardController::class)->name('dashboard');
    });
