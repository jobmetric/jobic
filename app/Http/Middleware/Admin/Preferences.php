<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Facades\Hooks;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Preferences
{
    public function handle(Request $request, Closure $next): Response
    {
        $preferences = array_replace(config('admin.appearance'), $request->user()?->ui_preferences ?? []);
        app()->setLocale(in_array($preferences['locale'], ['en', 'fa'], true) ? $preferences['locale'] : 'en');
        view()->share('adminPreferences', $preferences);
        $sections = [];
        foreach (config('admin.settings') as $key => $item) {
            $sections[$key] = $item + ['label' => trans('admin.sections.'.$key), 'description' => trans('admin.descriptions.'.$key),
                'url' => $key === 'mail' ? route('admin.settings.mail.edit') : route('admin.settings.edit', $key)];
        }
        view()->share('settingsSections', Hooks::filter('jobic.admin.settings.sections', $sections, ['user_id' => (int) $request->user()->id]));

        return $next($request);
    }
}
