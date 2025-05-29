<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web/routes.php',
        commands: __DIR__.'/../routes/console/routes.php',
        health: '/up',
        then: function (): void {
            Route::prefix('api/v1')
                ->middleware('api')
                ->group(base_path('routes/api/v1/routes.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {})->create();
