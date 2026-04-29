<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_mentor_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('mentor_role', 40);
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['student_user_id', 'mentor_user_id', 'mentor_role'], 'uq_student_mentor_role');
            $table->index(['mentor_user_id', 'mentor_role'], 'idx_mentor_role');
        });

        Schema::create('mentor_role_scopes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mentor_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('mentor_role', 40);
            $table->boolean('all_students_in_department')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mentor_user_id', 'mentor_role'], 'uq_mentor_role_scope');
        });

        // Backfill legacy pembimbing_user_id to new assignment table (role: pembimbing_pkl).
        DB::statement("
            INSERT INTO student_mentor_assignments (student_user_id, mentor_user_id, mentor_role, assigned_by, created_at, updated_at)
            SELECT u.id, u.pembimbing_user_id, 'pembimbing_pkl', u.updated_by, NOW(), NOW()
            FROM users u
            WHERE u.role = 'siswa'
              AND u.pembimbing_user_id IS NOT NULL
        ");

        // Backfill all-students department scope from existing flag.
        DB::statement("
            INSERT INTO mentor_role_scopes (mentor_user_id, mentor_role, all_students_in_department, updated_by, created_at, updated_at)
            SELECT u.id, 'pembimbing_pkl', 1, u.updated_by, NOW(), NOW()
            FROM users u
            WHERE u.role = 'pembimbing_pkl'
              AND COALESCE(u.is_school_mentor_all_students, 0) = 1
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('mentor_role_scopes');
        Schema::dropIfExists('student_mentor_assignments');
    }
};

