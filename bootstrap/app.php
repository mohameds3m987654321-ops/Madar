<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\TeacherMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // الثقة في كل الـ Proxies القادمة من Railway
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'teacher' => TeacherMiddleware::class,
            'verified-or-admin' => \App\Http\Middleware\VerifyEmailForNonAdmins::class,
            'two-factor' => \App\Http\Middleware\RequireTwoFactorAuthentication::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'payments/paymob/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();