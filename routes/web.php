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
    ? redirect()->route('admin.dashboard')
    : redirect()->route('login'))->name('home');

require __DIR__.'/auth.php';
Route::get('editor-assets/{key}/{kind}', \App\Http\Controllers\Editor\EditorAssetController::class)
    ->where('key', '[a-z][a-zA-Z0-9_-]*')->where('kind', 'module|style')->name('editor.assets');
require __DIR__.'/admin.php';
require __DIR__.'/vuero.php';
