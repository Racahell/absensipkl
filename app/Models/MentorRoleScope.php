<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorRoleScope extends Model
{
    protected $fillable = [
        'mentor_user_id',
        'mentor_role',
        'all_students_in_department',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'all_students_in_department' => 'boolean',
        ];
    }

    public function mentor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mentor_user_id');
    }
}

