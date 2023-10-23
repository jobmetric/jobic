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
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $env = base_path('.env');

        if(false === file_exists($env)) {
            setcookie('step-install', 'configuration', strtotime("+1 year"), '/');
            return redirect()->route('installation.intro');
        }

        if('' === env('INSTALLATION', '')) {
            setcookie('step-install', 'configuration', strtotime("+1 year"), '/');
            return redirect()->route('installation.intro');
        }

        return $next($request);
    }
}
