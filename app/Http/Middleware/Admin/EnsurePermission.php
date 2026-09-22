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
     * @param AdminAccess $access
     *
     * @return void
     */
    public function __construct(private AdminAccess $access)
    {
    }

    /**
     * Authorize the request against one of the supplied permission keys.
     *
     * @param Request $request
     * @param Closure $next
     * @param string ...$permissions
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $permissions = array_map(fn (string $permission
        ) => $permission === 'admin.settings.section' ? $this->access->settingsPermission((string) $request->route('section')) : $permission, $permissions,);

        if (! $this->access->allowsAny($request->user(), $permissions)) {
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
