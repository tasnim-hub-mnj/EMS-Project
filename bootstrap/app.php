<?php

use App\Http\Middleware\CheckAdminRole;
use App\Http\Middleware\CheckInvestorRole;
use App\Http\Middleware\CheckOrganizerRole;
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
    ->withMiddleware(function (Middleware $middleware): void 
    {
        $middleware->alias([
            'checkAdmin' => CheckAdminRole::class,
            'checkOrganizer' => CheckOrganizerRole::class,
            'checkInvestor' => CheckInvestorRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
