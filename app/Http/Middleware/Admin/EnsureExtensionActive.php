<?php

declare(strict_types=1);

namespace App\Http\Middleware\Admin;

use App\Support\Extensions\Runtime;
use Closure;
use Illuminate\Http\Request;
use JobMetric\Extension\Facades\ExtensionRegistry;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevent cached extension routes from running after their owner is disabled.
 */
class EnsureExtensionActive
{
    /**
     * Check controller namespaces and closure source paths against discovered extension ownership.
     *
     * @param  Request  $request
     * @param  Closure  $next
     *
     * @return Response
     * @throws \Throwable
     */
    public function handle(Request $request, Closure $next): Response
    {
        $action = $request->route()?->getAction('uses');
        foreach (array_unique(array_merge(ExtensionRegistry::values(), app('InstalledExtensionsFile')->allNamespaces(), app(Runtime::class)->knownNamespaces())) as $namespace) {
            $prefix = substr($namespace, 0, (int) strrpos($namespace, '\\')).'\\';
            $owned = is_string($action) && strncasecmp(ltrim($action, '\\'), $prefix, strlen($prefix)) === 0;
            if ($action instanceof Closure) {
                $file = (new \ReflectionFunction($action))->getFileName();
                $path = resolveNamespacePath($namespace);
                $directory = $path ? dirname(str_ends_with($path, '.php') ? $path : $path.'.php') : null;
                $owned = $file && $directory && str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $directory).'/');
            }
            abort_if($owned && ! app(Runtime::class)->active($namespace), 404);
        }

        return $next($request);
    }
}
