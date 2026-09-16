<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Page;
use Illuminate\View\View;

/**
 * Handle dashboard page requests.
 */
class DashboardController extends Controller
{
    /**
     * Render the dashboard page.
     *
     * @return View
     */
    public function __invoke(): View
    {
        Page::prepare('admin');

        return view('admin.dashboard');
    }
}
