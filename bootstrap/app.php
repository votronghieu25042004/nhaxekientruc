<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\HandleInertiaRequests;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/*',
            'scanner/verify',
            'scanner/checkin',
            'ai/*',
            'conductor/*',
            'driver/*',
            'register/send-otp',
            'register/verify-otp',
            'auth/send-otp',
            'auth/verify-otp',
            'admin/trips/*/assign-driver',
            'admin/users/*/toggle-status',
            'admin/users/*',
            'admin/roles/users/*/toggle-status',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $e) {
            error_log('*** UNHANDLED EXCEPTION: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        });
    })->create();