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

        $allowsRegistered = in_array('admin.settings.any', $permissions, true) && $this->access->allowsAnyRegistered($request->user(), 'admin.settings');

        if (! $allowsRegistered && ! $this->access->allowsAny($request->user(), array_diff($permissions, ['admin.settings.any']))) {
            $landingRoute = $this->access->landingRoute($request->user());

            if ($request->routeIs('admin.dashboard') && $landingRoute !== 'admin.dashboard') {
                return redirect()->route($landingRoute, $this->access->landingParameters($request->user()));
            }

            abort(403);
        }

        return $next($request);
    }
}
