<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = [
        'key',
        'name',
        'url',
    ];

    public function permissions(): HasMany
    {
        return $this->hasMany(MenuPermission::class);
    }
}
