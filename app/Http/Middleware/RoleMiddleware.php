<?php

namespace App\Http\Middleware;

use App\Support\MenuAccess;
use App\Support\MenuKeyResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->role === 'superadmin') {
            return $next($request);
        }

        $normalizedUserRole = $this->normalizeRole($user->role);
        $normalizedRoles = array_map(fn (string $role) => $this->normalizeRole($role), $roles);

        if (count($normalizedRoles) > 0 && ! in_array($normalizedUserRole, $normalizedRoles, true)) {
            $menuKey = MenuKeyResolver::resolve($request);
            if ($menuKey !== null && MenuAccess::canAccess($normalizedUserRole, $menuKey)) {
                return $next($request);
            }

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
