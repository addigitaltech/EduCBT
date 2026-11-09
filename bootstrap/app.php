<?php

use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Routes are registered per profile by App\Providers\ProfileServiceProvider,
// so only the console routes and the framework health route are declared here.
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AssignRequestId::class,
            SecurityHeaders::class,
            EnsureInstalled::class,
        ]);

        $middleware->alias([
            'account.active' => EnsureAccountActive::class,
            'password.changed' => EnsurePasswordChanged::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
