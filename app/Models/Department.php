<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'is_deleted',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'department_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'department_id');
    }
}
