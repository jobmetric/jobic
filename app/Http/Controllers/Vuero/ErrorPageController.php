<?php

namespace App\Http\Controllers\Vuero;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ErrorPageController extends Controller
{
    public function show(string $page): View
    {
        $config = config("vuero.error_pages.{$page}");

        abort_unless($config, 404);

        return view('vuero.error.show', [
            'page' => $config,
            'slug' => $page,
        ]);
    }
}
