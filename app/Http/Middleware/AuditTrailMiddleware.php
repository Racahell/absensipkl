<?php

namespace App\Http\Middleware;

use App\Support\DiscordNotifier;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->user()) {
            return $response;
        }

        if ($request->isMethod('get')) {
            return $response;
        }

        $columns = $this->activityLogColumns();
        $now = now();
        [$latitude, $longitude] = $this->resolveCoordinates($request);
        $data = [
            'user_id' => $request->user()->id,
            'action' => $this->buildAction($request),
            'method' => $request->method(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'payload' => json_encode($this->sanitizePayload($request), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        if (in_array('url', $columns, true)) {
            $data['url'] = $request->fullUrl();
        } elseif (in_array('path', $columns, true)) {
            $data['path'] = $request->path();
        }

        if (in_array('ip_address', $columns, true)) {
            $data['ip_address'] = $request->ip();
        } elseif (in_array('ip', $columns, true)) {
            $data['ip'] = $request->ip();
        }

        if (in_array('description', $columns, true)) {
            $data['description'] = 'Aksi tercatat otomatis oleh middleware audit.';
        }

        if (in_array('user_agent', $columns, true)) {
            $data['user_agent'] = (string) $request->userAgent();
        }

        try {
            DB::table('activity_logs')->insert(array_intersect_key($data, array_flip($columns)));
        } catch (\Throwable) {
            // ignore audit write errors so main business flow is not blocked
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            DiscordNotifier::notifyEditDelete('Notifikasi Aktivitas Sistem', [
                'Aktor' => $request->user()?->name.' ('.$request->user()?->role.')',
                'Method' => $request->method(),
                'Route' => $request->route()?->getName() ?? '-',
                'URL' => $request->fullUrl(),
                'IP' => $request->ip(),
                'Waktu' => now()->toDateTimeString(),
            ]);
        }

        return $response;
    }

    private function buildAction(Request $request): string
    {
        $routeName = strtolower((string) ($request->route()?->getName() ?? ''));
        $path = strtolower($request->path());
        $method = strtoupper($request->method());

        if (str_contains($routeName, 'login.store') || $path === 'login') {
            return 'login';
        }

        if (str_contains($routeName, 'logout') || $path === 'logout') {
            return 'logout';
        }

        return match ($method) {
            'DELETE' => 'delete',
            'PUT', 'PATCH' => 'update',
            'POST' => 'create',
            default => 'view',
        };
    }

    private function sanitizePayload(Request $request): array
    {
        return $request->except([
            'password',
            'password_confirmation',
            '_token',
        ]);
    }

    private function activityLogColumns(): array
    {
        static $columns;

        if ($columns !== null) {
            return $columns;
        }

        $columns = Schema::hasTable('activity_logs')
            ? Schema::getColumnListing('activity_logs')
            : [];

        return $columns;
    }

    private function resolveCoordinates(Request $request): array
    {
        $latitude = $this->normalizeCoordinate($request->input('latitude'), -90, 90);
        $longitude = $this->normalizeCoordinate($request->input('longitude'), -180, 180);

        if ($latitude !== null && $longitude !== null) {
            $request->session()->put('last_latitude', $latitude);
            $request->session()->put('last_longitude', $longitude);

            return [$latitude, $longitude];
        }

        $sessionLatitude = $this->normalizeCoordinate($request->session()->get('last_latitude'), -90, 90);
        $sessionLongitude = $this->normalizeCoordinate($request->session()->get('last_longitude'), -180, 180);

        return [$sessionLatitude, $sessionLongitude];
    }

    private function normalizeCoordinate(mixed $value, float $min, float $max): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $coordinate = (float) $value;

        if ($coordinate < $min || $coordinate > $max) {
            return null;
        }

        return round($coordinate, 7);
    }
}
