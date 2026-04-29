<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PklLocation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'radius_meters',
        'ip_reference',
        'pembimbing_user_id',
        'instruktur_user_id',
        'kajur_user_id',
        'is_deleted',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function pembimbing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pembimbing_user_id');
    }

    public function instruktur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instruktur_user_id');
    }

    public function kajur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kajur_user_id');
    }
}
