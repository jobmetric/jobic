<?php

use App\Extensions\Plugin\JobicPluginExample\Http\Controllers\ExampleController;
use App\Http\Middleware\Admin\EnsurePermission;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', Preferences::class, EnsurePermission::class.':admin.settings.extensions.examples.plugin'])
    ->get('admin/extension-examples/plugin', [ExampleController::class, 'index'])->name('admin.extension-examples.plugin');
