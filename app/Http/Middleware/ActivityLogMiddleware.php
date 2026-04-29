<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $user = $request->user();

        if (! $user) {
            return $response;
        }

        $action = match ($request->method()) {
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'view',
        };

        $payload = collect($request->except(['password', 'password_confirmation', 'current_password', '_token']))
            ->take(30)
            ->toArray();

        ActivityLog::create([
            'user_id' => $user->id,
            'method' => $request->method(),
            'path' => $request->path(),
            'action' => $action,
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'payload' => $payload,
            'created_at' => now(),
        ]);

        if (
            $user->role === 'superadmin'
            && in_array($action, ['edit', 'delete'], true)
            && filled(config('services.discord.webhook_url'))
        ) {
            Http::timeout(8)->post(config('services.discord.webhook_url'), [
                'content' => "Superadmin action: {$action}\nUser: {$user->name} ({$user->nis})\nPath: /{$request->path()}\nIP: {$request->ip()}\nTime: ".now()->format('Y-m-d H:i:s'),
            ]);
        }

        return $response;
    }
}
