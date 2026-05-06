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

        $normalizedRole = $this->normalizeRole((string) $user->role);
        $hasAccess = MenuAccess::canAccess($normalizedRole, $menuKey);

        if (! $hasAccess && $this->canUseSummaryReportValidationEndpoint($request, $normalizedRole, $menuKey)) {
            $hasAccess = true;
        }

        if (! $hasAccess && $this->canUseParentMenuAccess($normalizedRole, $menuKey)) {
            $hasAccess = true;
        }

        if (! $hasAccess) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }

    private function canUseSummaryReportValidationEndpoint(Request $request, string $normalizedRole, string $menuKey): bool
    {
        $routeName = (string) optional($request->route())->getName();
        if ($routeName !== 'guidance.mentor.validate') {
            return false;
        }

        if ($menuKey !== 'validasi/catatan-bimbingan') {
            return false;
        }

        return MenuAccess::canAccess($normalizedRole, 'summary-report');
    }

    private function normalizeRole(string $role): string
    {
        return match ($role) {
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
            'pembimbing' => 'pembimbing_pkl',
            default => $role,
        };
    }

    private function canUseParentMenuAccess(string $normalizedRole, string $menuKey): bool
    {
        return match ($menuKey) {
            'summary-report/rekap', 'summary-report/analisis' => MenuAccess::canAccess($normalizedRole, 'summary-report'),
            default => false,
        };
    }
}
