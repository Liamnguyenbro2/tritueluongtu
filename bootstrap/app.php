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
    ->withCommands([
        App\Console\Commands\DistributePoolShare::class,
        App\Console\Commands\ExpireTrials::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'not_suspended' => App\Http\Middleware\EnsureAccountIsNotSuspended::class,
            'admin' => App\Http\Middleware\EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
