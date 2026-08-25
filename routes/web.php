<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', fn () => auth()->check()
    ? redirect()->route('vuero.admin.dashboard')
    : redirect()->route('vuero.login'))->name('home');

require __DIR__.'/vuero.php';
