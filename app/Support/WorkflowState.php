<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class WorkflowState
{
    public const ATTENDANCE_PENDING = ['pending', 'pending_pembimbing'];
    public const ATTENDANCE_REJECTS = ['reject_checkin', 'reject_checkout'];
    public const LEAVE_PENDING = ['awaiting', 'pending_pembimbing', 'pending_instruktur', 'pending_kajur'];
    public const LEAVE_REJECTS = ['rejected', 'reject_izin', 'reject_sakit'];
    public const REPORT_PENDING = ['pending_pembimbing', 'pending_instruktur'];

    public static function isAttendanceAlphaRecap(string $status): bool
    {
        return $status === 'alpha' || in_array($status, self::ATTENDANCE_REJECTS, true);
    }

    public static function isLeaveAlphaRecap(string $status): bool
    {
        return $status === 'alpha' || in_array($status, self::LEAVE_REJECTS, true);
    }

    public static function defaultSlaDueAt(): Carbon
    {
        return now()->addHours(24);
    }

    public static function escalationLevel(Carbon $dueAt): ?string
    {
        if (now()->lte($dueAt)) {
            return null;
        }

        $overdueHours = $dueAt->diffInHours(now());

        if ($overdueHours >= 48) {
            return 'high';
        }

        if ($overdueHours >= 24) {
            return 'medium';
        }

        return 'low';
    }

}
