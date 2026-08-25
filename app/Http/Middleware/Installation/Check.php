<?php

namespace App\Http\Middleware\Installation;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Check
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('installation.enabled') || config('installation.completed')) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
