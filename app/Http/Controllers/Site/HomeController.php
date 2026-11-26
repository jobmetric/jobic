<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Support\Layouts\SiteThemes;
use App\Support\Layouts\ThemeManifest;
use App\Support\Page;
use Illuminate\View\View;

/**
 * Serve the public website homepage.
 */
class HomeController extends Controller
{
    /**
     * Prepare the homepage with the active theme's header and declared positions.
     *
     * @param SiteThemes $themes
     * @param ThemeManifest $manifest
     *
     *
     * @return View
     */
    public function __invoke(SiteThemes $themes, ThemeManifest $manifest): View
    {
        Page::prepare('site-home');
        Page::addStyle('css/site-home.css');
        Page::addScript('js/site-carousel.js');

        $theme = $themes->active();

        return view('site.home', [
            'positions' => $theme ? array_keys($manifest->positions($theme->namespace)) : [],
            'headerNavigation' => $themes->headerNavigation($theme),
        ]);
    }
}
