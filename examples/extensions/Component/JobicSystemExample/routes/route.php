<?php

use App\Extensions\Component\JobicSystemExample\Http\Controllers\ExampleController;
use App\Http\Middleware\Admin\EnsurePermission;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', Preferences::class, EnsurePermission::class.':admin.settings.extensions.examples.system'])
    ->get('admin/extension-examples/system', [ExampleController::class, 'index'])->name('admin.extension-examples.system');
