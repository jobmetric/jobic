<?php

use App\Http\Controllers\Installation\IntroController;
use Illuminate\Support\Facades\Route;

Route::controller(IntroController::class)->group(function (): void {
    Route::get('/', 'index')->name('intro');
    Route::get('/status', 'status')->name('status');
    Route::middleware('throttle:30,1')->group(function (): void {
        Route::post('/language', [IntroController::class, 'language'])->name('language');
        Route::post('/steps', 'save')->name('steps.store');
        Route::post('/test/database', 'database')->name('test.database');
        Route::post('/test/redis', [IntroController::class, 'redis'])->name('test.redis');
        Route::post('/start', 'start')->name('start');
        Route::post('/next', 'next')->name('next');
    });
});
