<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use Symfony\Component\HttpFoundation\Response;

/** Authorize a taxonomy operation using permissions configured on its registered type. */
readonly class EnsureTaxonomyPermission
{
    public function __construct(private AdminAccess $access)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $type = (string) $request->route('type');
        abort_unless(TaxonomyTypeRegistry::has($type), 404);

        $action = $request->route()
            ->getActionMethod();
        if (in_array($action, [
            'unlockEditLock',
            'unlockEditLocks',
        ], true)) {
            $permission = (string) TaxonomyTypeRegistry::getOption($type, 'unlock-permission', 'admin.taxonomies.unlock');
            abort_unless($this->access->allows($request->user(), $permission), 403);

            return $next($request);
        }

        $isRead = $request->isMethod('GET') && in_array($action, [
                'index',
                'data',
                'history',
            ], true);

        $option = $isRead ? 'view-permission' : 'manage-permission';
        $permission = (string) TaxonomyTypeRegistry::getOption($type, $option, 'admin.content');

        abort_unless($this->access->allows($request->user(), $permission), 403);

        return $next($request);
    }
}
