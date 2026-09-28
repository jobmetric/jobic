<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reject CMS requests without the required hierarchical Rolix permission.
 */
readonly class EnsurePermission
{
    /**
     * Create the CMS permission middleware.
     *
     *
     * @return void
     */
    public function __construct(private AdminAccess $access)
    {
    }

    /**
     * Authorize the request against one of the supplied permission keys.
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $permissions = array_map(fn (string $permission) => match ($permission) {
            'admin.settings.section' => $this->access->settingsPermission((string) $request->route('section')),
            'admin.settings.dynamic' => $this->access->dynamicSettingsPermission((string) $request->route('application'), (string) $request->route('key')),
            default => $permission,
        }, $permissions);

        $allowsDynamic = in_array('admin.settings.dynamic.any', $permissions, true) && $this->access->allowsAnyDynamicSetting($request->user());

        if (! $allowsDynamic && ! $this->access->allowsAny($request->user(), array_diff($permissions, ['admin.settings.dynamic.any']))) {
            $landingRoute = $this->access->landingRoute($request->user());

            if ($request->routeIs('admin.dashboard') && $landingRoute !== 'admin.dashboard') {
                return redirect()->route($landingRoute, $landingRoute === 'admin.settings.edit' ? [
                    'section' => 'general',
                ] : []);
            }

            abort(403);
        }

        return $next($request);
    }
}
