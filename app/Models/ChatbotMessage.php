<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    protected $fillable = [
        'user_id',
        'session_token',
        'role',
        'lang',
        'is_bot',
        'message',
        'intent_key',
        'confidence',
    ];

    protected function casts(): array
    {
        return [
            'is_bot' => 'boolean',
            'confidence' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

