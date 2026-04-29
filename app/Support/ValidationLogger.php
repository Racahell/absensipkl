<?php

namespace App\Support;

use App\Models\User;
use App\Models\ValidationLog;

class ValidationLogger
{
    public static function log(?User $actor, string $targetType, int $targetId, string $action, ?string $note = null, array $meta = []): void
    {
        ValidationLog::create([
            'actor_id' => $actor?->id,
            'actor_role' => $actor?->role,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'action' => $action,
            'note' => $note,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}

