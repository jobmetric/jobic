<?php

declare(strict_types=1);

namespace App\Http\Middleware\Installation;

use App\Services\Installation\Installation;
use App\Support\Installation\Environment;
use App\Support\Installation\State;
use App\Support\LocaleCatalog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Apply to install middleware to requests.
 */
class Install
{
    /**
     * Restrict application access until installation is complete.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->bound('installation.bootstrap_error')) {
            return response('Jobic: امکان آماده‌سازی فایل تنظیمات وجود ندارد. کلید APP_KEY، قالب env و دسترسی نوشتن .env و bootstrap/cache را بررسی کنید.', 503);
        }

        $installation = app(Installation::class);
        try {
            $completed = $installation->completed() || $installation->detectExisting();
        } catch (Throwable) {
            return response('Jobic: امکان خواندن وضعیت نصب وجود ندارد.', 503);
        }

        if ($completed) {
            if ($request->is('installation', 'installation/*')) {
                return $request->expectsJson() ? response()->json([
                    'ok'      => false,
                    'message' => trans('installation.closed'),
                    'data'    => ['completed' => true],
                ], 403) : redirect()->route('login');
            }

            return $next($request);
        }

        $directory = app(State::class)->directory;
        foreach (['sessions', 'cache'] as $folder) {
            File::ensureDirectoryExists($directory . '/' . $folder, 0700);
        }

        $locale = app(Environment::class)->read()['INSTALLATION_LOCALE'] ?? 'en';
        app()->setLocale(LocaleCatalog::resolve($locale));
        config([
            'app.debug'                 => false,
            'session.driver'            => 'file',
            'session.cookie'            => 'jobic_installer',
            'session.files'             => $directory . '/sessions',
            'session.path'              => '/',
            'session.domain'            => null,
            'session.secure'            => $request->isSecure(),
            'session.same_site'         => 'lax',
            'session.encrypt'           => true,
            'session.connection'        => null,
            'session.store'             => null,
            'cache.default'             => 'installation',
            'cache.stores.installation' => [
                'driver'    => 'file',
                'path'      => $directory . '/cache',
                'lock_path' => $directory . '/cache',
            ],
            'logging.default'           => 'single',
        ]);

        if (! $request->is('installation', 'installation/*')) {
            return $request->is('api', 'api/*') || $request->expectsJson() ? response()->json([
                'ok'               => false,
                'message'          => trans('installation.required'),
                'installation_url' => route('installation.intro'),
            ], 503) : redirect()->route('installation.intro');
        }

        $response = $next($request);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
