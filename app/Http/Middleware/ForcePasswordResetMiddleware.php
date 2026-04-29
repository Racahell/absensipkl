<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordResetMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $mustChangePassword = (bool) ($user->must_change_password ?? $user->must_reset_password);

        if (
            $mustChangePassword
            && ! $request->routeIs(
                'password.reset.edit',
                'password.reset.update',
                'logout',
                'verification.notice',
                'verification.verify',
                'verification.send'
            )
        ) {
            return redirect()->route('password.reset.edit');
        }

        return $next($request);
    }
}
