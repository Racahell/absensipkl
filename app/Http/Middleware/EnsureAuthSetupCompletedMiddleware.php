<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthSetupCompletedMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ((bool) ($user->must_change_password ?? $user->must_reset_password)) {
            return $next($request);
        }

        $ready = ! empty($user->email_verified_at) && ! empty($user->phone_verified_at);
        if ($ready) {
            return $next($request);
        }

        if ($request->routeIs(
            'auth.setup.*',
            'logout',
            'password.reset.edit',
            'password.reset.update',
            'verification.notice',
            'verification.verify',
            'verification.send'
        )) {
            return $next($request);
        }

        return redirect()->route('auth.setup.show');
    }
}
