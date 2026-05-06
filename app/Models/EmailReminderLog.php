<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailReminderLog extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'reminder_type',
        'status',
        'message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }
}

