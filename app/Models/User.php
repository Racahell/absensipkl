<?php

namespace App\Models;

use App\Models\Builders\UserBuilder;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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
        'role',
        'nis',
        'nuptk',
        'role_id',
        'class_name',
        'school_class_id',
        'department_name',
        'department_id',
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

    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            $user->syncRoleColumns();
            $user->syncDepartmentColumns();
            $user->syncClassColumns();
        });
    }

    public function newEloquentBuilder($query): EloquentBuilder
    {
        return new UserBuilder($query);
    }

    public function pklLocation(): BelongsTo
    {
        return $this->belongsTo(PklLocation::class);
    }

    public function roleRef(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function departmentRef(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function schoolClassRef(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'school_class_id');
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

    private function syncRoleColumns(): void
    {
        $role = strtolower(trim((string) ($this->attributes['role'] ?? '')));
        $roleId = $this->role_id ? (int) $this->role_id : null;

        if ($role !== '') {
            $id = DB::table('roles')->where('key', $role)->value('id');
            if ($id) {
                $this->role_id = (int) $id;
            }
            unset($this->attributes['role']);
            return;
        }

        unset($this->attributes['role']);

        if ($roleId) {
            return;
        }
    }

    public function getRoleAttribute(): ?string
    {
        $roleId = $this->role_id ? (int) $this->role_id : null;
        if (! $roleId) {
            return null;
        }

        $key = DB::table('roles')->where('id', $roleId)->value('key');
        return (is_string($key) && $key !== '') ? $key : null;
    }

    public function setRoleAttribute(mixed $value): void
    {
        $roleKey = strtolower(trim((string) $value));
        if ($roleKey === '') {
            return;
        }

        $roleId = DB::table('roles')->where('key', $roleKey)->value('id');
        if ($roleId) {
            $this->attributes['role_id'] = (int) $roleId;
        }

        unset($this->attributes['role']);
    }

    private function syncDepartmentColumns(): void
    {
        $departmentName = trim((string) ($this->department_name ?? ''));
        $departmentId = $this->department_id ? (int) $this->department_id : null;

        if ($departmentName !== '') {
            $id = DB::table('departments')->where('name', $departmentName)->value('id');
            if (! $id) {
                DB::table('departments')->insert([
                    'name' => $departmentName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $id = DB::table('departments')->where('name', $departmentName)->value('id');
            }
            $this->department_id = $id;
            return;
        }

        if ($departmentId) {
            $name = DB::table('departments')->where('id', $departmentId)->value('name');
            if ($name) {
                $this->department_name = $name;
            }
        }
    }

    private function syncClassColumns(): void
    {
        $className = trim((string) ($this->class_name ?? ''));
        $classId = $this->school_class_id ? (int) $this->school_class_id : null;
        $departmentId = $this->department_id ? (int) $this->department_id : null;

        if ($className !== '') {
            $class = DB::table('school_classes')->where('name', $className)->first(['id', 'department_id']);
            if (! $class) {
                DB::table('school_classes')->insert([
                    'name' => $className,
                    'department_id' => $departmentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $class = DB::table('school_classes')->where('name', $className)->first(['id', 'department_id']);
            } elseif (! $class->department_id && $departmentId) {
                DB::table('school_classes')->where('id', $class->id)->update([
                    'department_id' => $departmentId,
                    'updated_at' => now(),
                ]);
                $class->department_id = $departmentId;
            }

            $this->school_class_id = $class?->id;

            if (! $this->department_id && $class?->department_id) {
                $this->department_id = (int) $class->department_id;
                $deptName = DB::table('departments')->where('id', $this->department_id)->value('name');
                if ($deptName) {
                    $this->department_name = $deptName;
                }
            }
            return;
        }

        if ($classId) {
            $class = DB::table('school_classes')->where('id', $classId)->first(['name', 'department_id']);
            if ($class) {
                $this->class_name = (string) $class->name;
                if (! $this->department_id && $class->department_id) {
                    $this->department_id = (int) $class->department_id;
                    $deptName = DB::table('departments')->where('id', $this->department_id)->value('name');
                    if ($deptName) {
                        $this->department_name = $deptName;
                    }
                }
            }
        }
    }
}
