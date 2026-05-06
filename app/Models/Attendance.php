<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'pkl_location_id',
        'attendance_date',
        'check_in_at',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_ip',
        'check_in_device',
        'check_in_location_label',
        'check_in_location_address',
        'check_in_selfie_path',
        'check_in_request_token',
        'check_out_at',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_ip',
        'check_out_device',
        'check_out_location_label',
        'check_out_location_address',
        'check_out_summary',
        'check_out_request_token',
        'session_status',
        'status',
        'validation_status',
        'checkin_validation_status',
        'checkout_validation_status',
        'reject_reason_code',
        'pembimbing_note',
        'instruktur_note',
        'kajur_note',
        'validated_by_pembimbing',
        'validated_by_instruktur',
        'validated_by_kajur',
        'validated_pembimbing_at',
        'validated_instruktur_at',
        'validated_kajur_at',
        'validation_sla_due_at',
        'validation_escalated_at',
        'validation_escalation_level',
        'is_deleted',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'check_in_at' => 'datetime',
            'check_out_at' => 'datetime',
            'validated_pembimbing_at' => 'datetime',
            'validated_instruktur_at' => 'datetime',
            'validated_kajur_at' => 'datetime',
            'validation_sla_due_at' => 'datetime',
            'validation_escalated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(PklLocation::class, 'pkl_location_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(DailyReport::class);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(StatusLog::class);
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(Assessment::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(AttendanceException::class);
    }
}
