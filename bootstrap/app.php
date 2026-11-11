<?php

use App\Http\Middleware\Installation\Install;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Installation\Bootstrap;
use App\Support\Page;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->name('api.')
                ->group(base_path('routes/api.php'));

            Route::middleware('installation')
                ->prefix('installation')
                ->name('installation.')
                ->group(base_path('routes/installation.php'));
        },
    )
    ->withBroadcasting(__DIR__.'/../routes/channels.php', ['middleware'=>['web','auth']])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        $middleware->trimStrings(except: ['DB_PASSWORD', 'REDIS_PASSWORD', 'MAIL_PASSWORD', 'ADMIN_PASSWORD', 'ADMIN_PASSWORD_confirmation']);
        $middleware->prepend(Install::class);

        $middleware->appendToGroup('web', [
            SecurityHeaders::class,
        ]);

        $middleware->group('installation', [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ValidateCsrfToken::class,
            SubstituteBindings::class,
            SecurityHeaders::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['code', 'DB_PASSWORD', 'REDIS_PASSWORD', 'MAIL_PASSWORD', 'ADMIN_PASSWORD', 'ADMIN_PASSWORD_confirmation']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() === 403 && $request->is('admin/*') && $request->user() && ! $request->expectsJson()) {
                Page::prepare('admin-error', app()->getLocale());

                return response()->view('errors.admin-403', status: 403);
            }
        });
    })->create();

$app->beforeBootstrapping(LoadEnvironmentVariables::class, fn ($app) => Bootstrap::prepare($app));

return $app;
