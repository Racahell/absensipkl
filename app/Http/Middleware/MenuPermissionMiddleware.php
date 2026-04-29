<?php

namespace App\Http\Middleware;

use App\Support\MenuAccess;
use App\Support\MenuKeyResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MenuPermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($this->normalizeRole((string) $user->role) === 'superadmin') {
            return $next($request);
        }

        $menuKey = MenuKeyResolver::resolve($request);
        if ($menuKey === null) {
            return $next($request);
        }

        if (! MenuAccess::canAccess($this->normalizeRole((string) $user->role), $menuKey)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
            default => $role,
        };
    }
}
