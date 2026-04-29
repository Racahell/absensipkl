<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaPasswordReset extends Model
{
    protected $fillable = [
        'phone',
        'code',
        'expires_at',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }
}
