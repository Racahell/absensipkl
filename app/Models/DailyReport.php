<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attendance_id',
        'plan_work',
        'plan_items',
        'actual_work',
        'actual_items',
        'assigned_task',
        'special_assignment',
        'field_issue',
        'remember_note',
        'evidence_path',
        'review_status',
        'pembimbing_review_note',
        'instruktur_review_note',
        'review_note_instruktur',
        'kajur_review_note',
        'reject_reason_code',
        'reviewed_by_pembimbing',
        'reviewed_by_instruktur',
        'reviewed_by_kajur',
        'reviewed_pembimbing_at',
        'reviewed_instruktur_at',
        'reviewed_kajur_at',
        'review_sla_due_at',
        'review_escalated_at',
        'review_escalation_level',
        'is_deleted',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_pembimbing_at' => 'datetime',
            'reviewed_instruktur_at' => 'datetime',
            'reviewed_kajur_at' => 'datetime',
            'review_sla_due_at' => 'datetime',
            'review_escalated_at' => 'datetime',
            'plan_items' => 'array',
            'actual_items' => 'array',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }
}
