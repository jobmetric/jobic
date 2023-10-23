<?php

use App\Http\Controllers\Installation\IntroController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Installation Routes
|--------------------------------------------------------------------------
|
| Here is where you can register installation routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them
| will be assigned to the "installation" middleware group. Make
| something great!
|
*/

Route::middleware('installation.check')->group(function (){
    Route::get('/', [IntroController::class, 'index'])->name('intro');
});
