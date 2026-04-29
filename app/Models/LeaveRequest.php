<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'pkl_location_id',
        'request_date',
        'type',
        'reason',
        'evidence_path',
        'status',
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
            'request_date' => 'date',
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
}
