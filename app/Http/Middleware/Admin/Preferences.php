<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Facades\Hooks;
use App\Services\Settings\DynamicSettings;
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

        $descriptors = app(DynamicSettings::class)->descriptors();
        foreach ($descriptors as $descriptor) {
            $permission = $access->dynamicSettingsPermission($descriptor['application'], $descriptor['key']);
            if (! $access->allows($request->user(), $permission)) {
                continue;
            }

            $sectionKey = 'dynamic-' . $descriptor['form_name'];
            $sections[$sectionKey] = [
                'icon'        => $descriptor['icon'],
                'group'       => 'ordinary',
                'order'       => 100,
                'label'       => trans($descriptor['title']),
                'description' => trans($descriptor['description']),
                'url'         => route('admin.settings.forms.edit', [$descriptor['application'], $descriptor['key']]),
                'permission'  => $permission,
            ];
        }

        view()->share('settingsSections', Hooks::filter('jobic.admin.settings.sections', $sections, [
            'user_id' => (int) $request->user()->id,
        ]));

        return $next($request);
    }
}
