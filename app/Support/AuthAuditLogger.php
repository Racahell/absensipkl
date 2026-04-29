<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuthAuditLogger
{
    /**
     * @param array<string,mixed> $payload
     */
    public function log(?int $userId, string $action, array $payload = []): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        $columns = Schema::getColumnListing('activity_logs');
        $data = [
            'user_id' => $userId,
            'action' => $action,
            'method' => 'AUTH',
            'path' => 'auth',
            'url' => 'auth://'.$action,
            'ip' => request()?->ip(),
            'ip_address' => request()?->ip(),
            'user_agent' => (string) request()?->userAgent(),
            'description' => 'Auth event: '.$action,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            DB::table('activity_logs')->insert(array_intersect_key($data, array_flip($columns)));
        } catch (\Throwable) {
            // Do not block login/auth flow if logging fails.
        }
    }
}

