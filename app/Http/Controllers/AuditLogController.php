<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $scope = strtolower(trim((string) $request->query('scope', 'all')));
        if (! in_array($scope, ['all', 'auth'], true)) {
            $scope = 'all';
        }

        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 30);
        if (! in_array($perPage, [20, 30, 50, 100], true)) {
            $perPage = 30;
        }

        $query = ActivityLog::query()->with('user');

        if ($scope === 'auth') {
            $query->where(function ($builder): void {
                $builder->where('method', 'AUTH')
                    ->orWhere('action', 'like', 'auth.%')
                    ->orWhere('path', 'like', '%login%')
                    ->orWhere('path', 'like', '%logout%')
                    ->orWhere('url', 'like', '%login%')
                    ->orWhere('url', 'like', '%logout%');
            });
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('action', 'like', '%'.$q.'%')
                    ->orWhere('path', 'like', '%'.$q.'%')
                    ->orWhere('url', 'like', '%'.$q.'%')
                    ->orWhere('method', 'like', '%'.$q.'%')
                    ->orWhere('ip', 'like', '%'.$q.'%')
                    ->orWhere('ip_address', 'like', '%'.$q.'%')
                    ->orWhereHas('user', function ($u) use ($q): void {
                        $u->where('name', 'like', '%'.$q.'%')
                            ->orWhere('username', 'like', '%'.$q.'%')
                            ->orWhere('email', 'like', '%'.$q.'%')
                            ->orWhere('nis', 'like', '%'.$q.'%')
                            ->orWhere('nuptk', 'like', '%'.$q.'%');
                    });
            });
        }

        return view('audit-logs.index', [
            'title' => 'Log Activity',
            'logs' => $query->latest()->paginate($perPage)->withQueryString(),
            'filters' => [
                'scope' => $scope,
                'q' => $q,
                'per_page' => $perPage,
            ],
        ]);
    }
}
