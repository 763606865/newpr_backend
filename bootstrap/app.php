<?php

use App\B\Middleware\BizPlanMiddleware;
use App\Exceptions\UnauthenticatedException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;
use Laravel\Passport\Exceptions\OAuthServerException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function () {
            Route::prefix('b')->group(base_path('routes/b.php'));
            Route::prefix('cms')->group(base_path('routes/cms.php'));
            Route::prefix('rc')->group(base_path('routes/rc.php'));
            Route::prefix('sapi')->group(base_path('routes/sapi.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->use([HandleCors::class]);
        $middleware->alias([
            'biz-plan' => BizPlanMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                if ($e instanceof AuthenticationException) {
                    throw new UnauthenticatedException('Token expired or invalid.');
                }
                if ($e instanceof OAuthServerException) {
                    throw new UnauthenticatedException('Token expired or invalid.');
                }
            }
        });
    })->create();
