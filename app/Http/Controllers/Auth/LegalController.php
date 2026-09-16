<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\Page;
use Illuminate\View\View;

/**
 * Handle legal page requests.
 */
class LegalController extends Controller
{
    /**
     * Render the privacy policy page.
     *
     * @return View
     */
    public function privacy(): View
    {
        app()->setLocale(session('auth_locale', 'fa'));
        Page::prepare('legal');

        return view('legal.page', ['policy' => 'privacy']);
    }

    /**
     * Render the cookie policy page.
     *
     * @return View
     */
    public function cookies(): View
    {
        app()->setLocale(session('auth_locale', 'fa'));
        Page::prepare('legal');

        return view('legal.page', ['policy' => 'cookies']);
    }
}
