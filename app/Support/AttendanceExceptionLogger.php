<?php

namespace App\Support;

use App\Models\Attendance;
use App\Models\AttendanceException;
use App\Models\User;

class AttendanceExceptionLogger
{
    public static function log(
        User $user,
        string $type,
        string $severity = 'medium',
        array $meta = [],
        ?Attendance $attendance = null
    ): void {
        AttendanceException::query()->create([
            'user_id' => $user->id,
            'attendance_id' => $attendance?->id,
            'event_date' => today(),
            'exception_type' => $type,
            'severity' => $severity,
            'meta' => $meta,
        ]);
    }
}

