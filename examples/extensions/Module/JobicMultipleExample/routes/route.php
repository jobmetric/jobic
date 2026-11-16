<?php

use App\Extensions\Module\JobicMultipleExample\Http\Controllers\ExampleController;
use App\Http\Middleware\Admin\EnsurePermission;
use App\Http\Middleware\Admin\Preferences;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', Preferences::class, EnsurePermission::class.':admin.settings.extensions.examples.multiple'])
    ->get('admin/extension-examples/multiple', [ExampleController::class, 'index'])->name('admin.extension-examples.multiple');
