<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => \App\Http\Middleware\IsAdminMiddleware::class,
            // ... inne twoje aliasy, jeśli je masz
            // np. 'auth' => \App\Http\Middleware\Authenticate::class, (jeśli używasz własnego)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
