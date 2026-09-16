<?php

namespace App\Http\Controllers\Vuero;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Handle error page page requests.
 */
class ErrorPageController extends Controller
{
    /**
     * Render the requested page.
     *
     * @param string $page
     *
     * @return View
     */
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
