<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Page;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View
    {
        Page::prepare('auth', 'fa');

        return view('legal.privacy');
    }

    public function cookies(): View
    {
        Page::prepare('auth', 'fa');

        return view('legal.cookies');
    }
}
