<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'nis',
        'nuptk',
        'role',
        'class_name',
        'department_name',
        'pkl_location_id',
        'pembimbing_user_id',
        'is_school_mentor_all_students',
        'email',
        'pending_email',
        'phone',
        'google_id',
        'is_google_linked',
        'is_otp_active',
        'phone_verified_at',
        'profile_photo_path',
        'password',
        'must_reset_password',
        'must_change_password',
        'last_login_at',
        'last_login_ip',
        'created_by',
        'updated_by',
        'deleted_by',
        'is_deleted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'is_google_linked' => 'boolean',
            'is_otp_active' => 'boolean',
            'must_reset_password' => 'boolean',
            'must_change_password' => 'boolean',
            'is_school_mentor_all_students' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function pklLocation(): BelongsTo
    {
        return $this->belongsTo(PklLocation::class);
    }

    public function pembimbing(): BelongsTo
    {
        return $this->belongsTo(self::class, 'pembimbing_user_id');
    }

    public function bimbinganSiswa(): HasMany
    {
        return $this->hasMany(self::class, 'pembimbing_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(self::class, 'deleted_by');
    }
}
