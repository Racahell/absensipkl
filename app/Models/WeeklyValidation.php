<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyValidation extends Model
{
    protected $fillable = [
        'week_start',
        'week_end',
        'department_name',
        'class_name',
        'status',
        'note',
        'instruktur_note',
        'kajur_note',
        'validated_by',
        'noted_by_instruktur',
        'noted_by_kajur',
        'validated_at',
        'noted_instruktur_at',
        'noted_kajur_at',
        'approved_by_kajur',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'validated_at' => 'datetime',
            'noted_instruktur_at' => 'datetime',
            'noted_kajur_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function approverKajur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_kajur');
    }
}
