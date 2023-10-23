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
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if('' !== env('INSTALLATION', '')) {
            return redirect()->route('home');
        }

        return $next($request);
    }
}
