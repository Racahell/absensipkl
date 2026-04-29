<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use App\Http\Middleware\AuditTrailMiddleware;
use App\Http\Middleware\EnsureAuthSetupCompletedMiddleware;
use App\Http\Middleware\ForcePasswordResetMiddleware;
use App\Http\Middleware\MenuPermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $exceptions->render(function (\Throwable $exception, $request) {
            if ($request->expectsJson()) {
                return null;
            }

            if ($exception instanceof ValidationException) {
                return null;
            }
            
            if ($exception instanceof AuthenticationException) {
                return redirect()->guest(route('login'));
            }

            $status = $exception instanceof HttpExceptionInterface
                ? $exception->getStatusCode()
                : 500;

            if (view()->exists('errors.'.$status)) {
                return response()->view('errors.'.$status, [], $status);
            }

            return response()->view('errors.500', [], 500);
        });
    })->create();
