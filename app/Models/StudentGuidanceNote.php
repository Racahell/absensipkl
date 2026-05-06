<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentGuidanceNote extends Model
{
    protected $fillable = [
        'student_user_id',
        'guidance_date',
        'student_note',
        'student_submitted_at',
        'mentor1_user_id',
        'mentor2_user_id',
        'mentor1_status',
        'mentor1_note',
        'mentor1_validated_by',
        'mentor1_validated_at',
        'mentor2_status',
        'mentor2_note',
        'mentor2_validated_by',
        'mentor2_validated_at',
        'kajur_note',
        'kajur_noted_by',
        'kajur_noted_at',
        'wakil_status',
        'wakil_note',
        'wakil_validated_by',
        'wakil_validated_at',
        'final_attendance_status',
    ];

    protected function casts(): array
    {
        return [
            'guidance_date' => 'date',
            'student_submitted_at' => 'datetime',
            'mentor1_validated_at' => 'datetime',
            'mentor2_validated_at' => 'datetime',
            'kajur_noted_at' => 'datetime',
            'wakil_validated_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function mentor1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor1_user_id');
    }

    public function mentor2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor2_user_id');
    }
}
