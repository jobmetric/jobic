<?php

namespace App\Http\Middleware\Installation;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Install
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('installation.enabled') || config('installation.completed')) {
            return $next($request);
        }

        setcookie('step-install', 'configuration', strtotime('+1 year'), '/');

        return redirect()->route('installation.intro');
    }
}
