<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Page;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Page::prepare('admin');

        return view('admin.dashboard');
    }
}
