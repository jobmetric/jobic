<?php

use App\Extensions\Module\JobicSingleExample\Http\Controllers\ExampleController;
use App\Http\Middleware\Admin\EnsurePermission;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', Preferences::class, EnsurePermission::class.':admin.settings.extensions.examples.single'])
    ->get('admin/extension-examples/single', [ExampleController::class, 'index'])->name('admin.extension-examples.single');
