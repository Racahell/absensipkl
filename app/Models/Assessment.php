<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'attendance_id',
        'assessor_user_id',
        'senyum_baik',
        'senyum',
        'keramahan_baik',
        'keramahan',
        'penampilan_baik',
        'penampilan',
        'komunikasi_baik',
        'komunikasi',
        'realisasi_kerja_baik',
        'realisasi_kerja',
        'note',
        'is_deleted',
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_user_id');
    }
}
