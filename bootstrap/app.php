<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AuditTrailMiddleware;
use App\Http\Middleware\EnsureAuthSetupCompletedMiddleware;
use App\Http\Middleware\ForcePasswordResetMiddleware;
use App\Http\Middleware\MenuPermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'menu.permission' => MenuPermissionMiddleware::class,
            'force.password.reset' => ForcePasswordResetMiddleware::class,
            'auth.setup.completed' => EnsureAuthSetupCompletedMiddleware::class,
        ]);

        $middleware->web(append: [
            AuditTrailMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Use Laravel default exception rendering to avoid early-container failures.
    })->create();
