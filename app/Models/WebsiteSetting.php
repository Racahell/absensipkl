<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'site_title',
        'logo_path',
        'favicon_path',
        'address',
        'manager_name',
        'contact',
        'updated_by',
    ];
}
