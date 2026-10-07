<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use JobMetric\Post\Facades\PostTypeRegistry;
use Symfony\Component\HttpFoundation\Response;

/** Authorize post operations using the permissions configured for each post type. */
final readonly class EnsurePostPermission
{
    public function __construct(private AdminAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $type = (string) $request->route('type');
        abort_unless(PostTypeRegistry::has($type), 404);
        $read = $request->isMethod('GET') && in_array($request->route()->getActionMethod(), ['index', 'data', 'show'], true);
        $option = $read ? 'view-permission' : 'manage-permission';
        $fallback = $read ? 'admin.posts.view' : 'admin.posts.manage';
        abort_unless($this->access->allows($request->user(), (string) PostTypeRegistry::getOption($type, $option, $fallback)), 403);
        return $next($request);
    }
}
