<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::create('school_classes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100)->unique();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->timestamps();
        });

        // Seed roles from current users.role values + known defaults.
        $defaultRoles = [
            'superadmin' => 'Superadmin',
            'admin_sekolah' => 'Admin Sekolah',
            'siswa' => 'Siswa',
            'pembimbing_pkl' => 'Pembimbing PKL',
            'instruktur' => 'Instruktur',
            'kajur' => 'Kajur',
            'wali_kelas' => 'Wali Kelas',
            'kesiswaan' => 'Kesiswaan',
            'kepsek' => 'Kepsek',
            'wakil_kepsek' => 'Wakil Kepsek',
        ];

        foreach ($defaultRoles as $key => $name) {
            DB::table('roles')->updateOrInsert(
                ['key' => $key],
                ['name' => $name, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        $existingRoleKeys = DB::table('users')
            ->whereNotNull('role')
            ->where('role', '!=', '')
            ->distinct()
            ->pluck('role')
            ->map(fn ($v) => strtolower(trim((string) $v)))
            ->filter()
            ->values();

        foreach ($existingRoleKeys as $key) {
            DB::table('roles')->updateOrInsert(
                ['key' => $key],
                ['name' => ucwords(str_replace('_', ' ', $key)), 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // Seed departments and classes from current users data.
        $departments = DB::table('users')
            ->whereNotNull('department_name')
            ->where('department_name', '!=', '')
            ->distinct()
            ->pluck('department_name')
            ->map(fn ($v) => trim((string) $v))
            ->filter()
            ->values();

        foreach ($departments as $name) {
            DB::table('departments')->updateOrInsert(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $classes = DB::table('users')
            ->select(['class_name', 'department_name'])
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->distinct()
            ->get();

        foreach ($classes as $row) {
            $className = trim((string) ($row->class_name ?? ''));
            if ($className === '') {
                continue;
            }

            $departmentName = trim((string) ($row->department_name ?? ''));
            $departmentId = null;
            if ($departmentName !== '') {
                $departmentId = DB::table('departments')->where('name', $departmentName)->value('id');
            }

            $existing = DB::table('school_classes')->where('name', $className)->first(['id', 'department_id']);
            if (! $existing) {
                DB::table('school_classes')->insert([
                    'name' => $className,
                    'department_id' => $departmentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif (! $existing->department_id && $departmentId) {
                DB::table('school_classes')->where('id', $existing->id)->update([
                    'department_id' => $departmentId,
                    'updated_at' => now(),
                ]);
            }
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->after('department_name')->constrained('departments')->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->after('class_name')->constrained('school_classes')->nullOnDelete();
        });

        // Backfill user relation columns from existing denormalized text columns.
        DB::statement("
            UPDATE users u
            LEFT JOIN roles r ON r.key = LOWER(TRIM(u.role))
            SET u.role_id = r.id
            WHERE u.role_id IS NULL
        ");

        DB::statement("
            UPDATE users u
            LEFT JOIN departments d ON d.name = TRIM(u.department_name)
            SET u.department_id = d.id
            WHERE u.department_id IS NULL
        ");

        DB::statement("
            UPDATE users u
            LEFT JOIN school_classes c ON c.name = TRIM(u.class_name)
            SET u.school_class_id = c.id
            WHERE u.school_class_id IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
            $table->dropConstrainedForeignId('department_id');
            $table->dropConstrainedForeignId('school_class_id');
        });

        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('roles');
    }
};

