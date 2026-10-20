<?php

use App\Http\Controllers\Api\ApiTestController;
use App\Http\Controllers\Api\PublishedContentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('test', [ApiTestController::class, 'index'])->name('test');
Route::get('content/{type}/{post}', [PublishedContentController::class, 'show'])
    ->whereNumber('post')->name('content.show');
