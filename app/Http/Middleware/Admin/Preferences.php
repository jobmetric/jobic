<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Facades\Hooks;
use App\Support\AdminAccess;
use App\Support\LocaleCatalog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Apply preferences middleware to requests.
 */
class Preferences
{
    /**
     * Apply account locale and appearance preferences to the request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->status === false) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('auth.failed'),
                ], 401);
            }

            return redirect()->route('login');
        }

        $preferences = array_replace(config('admin.appearance'), $request->user()?->ui_preferences ?? []);
        app()->setLocale(LocaleCatalog::resolve($preferences['locale']));
        view()->share('adminPreferences', $preferences);

        $sections = [];
        $access = app(AdminAccess::class);
        foreach (config('admin.settings') as $key => $item) {
            if (! $access->allows($request->user(), $access->settingsPermission($key))) {
                continue;
            }

            $sections[$key] = $item + [
                    'label'       => trans('admin.sections.' . $key),
                    'description' => trans('admin.descriptions.' . $key),
                    'url'         => match ($key) {
                        'health' => route('admin.settings.health'),
                        'cache' => route('admin.settings.cache.edit'),
                        'mail' => route('admin.settings.mail.edit'),
                        'languages' => route('admin.settings.languages.index'),
                        default => route('admin.settings.edit', $key)
                    },
                ];
        }

        view()->share('settingsSections', Hooks::filter('jobic.admin.settings.sections', $sections, [
            'user_id' => (int) $request->user()->id,
        ]));

        return $next($request);
    }
}
