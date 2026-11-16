<?php

use App\Extensions\Theme\JobicThemeExample\Http\Controllers\ExampleController;
use App\Http\Middleware\Admin\EnsurePermission;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', Preferences::class, EnsurePermission::class.':admin.settings.extensions.examples.theme'])
    ->get('admin/extension-examples/theme', [ExampleController::class, 'index'])->name('admin.extension-examples.theme');
