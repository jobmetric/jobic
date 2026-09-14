<?php

use App\Http\Controllers\Installation\IntroController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IntroController::class, 'index'])->name('intro');
Route::get('/status', [IntroController::class, 'status'])->name('status');
Route::middleware('throttle:30,1')->group(function (): void {
    Route::post('/language', [IntroController::class, 'language'])->name('language');
    Route::post('/steps', [IntroController::class, 'save'])->name('steps.store');
    Route::post('/test/database', [IntroController::class, 'database'])->name('test.database');
    Route::post('/test/redis', [IntroController::class, 'redis'])->name('test.redis');
    Route::post('/start', [IntroController::class, 'start'])->name('start');
    Route::post('/next', [IntroController::class, 'next'])->name('next');
});
