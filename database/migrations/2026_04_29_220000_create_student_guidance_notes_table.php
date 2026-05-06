<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_guidance_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('guidance_date');
            $table->text('student_note');
            $table->timestamp('student_submitted_at')->nullable();

            $table->foreignId('mentor1_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mentor2_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('mentor1_status', 20)->default('pending');
            $table->text('mentor1_note')->nullable();
            $table->foreignId('mentor1_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mentor1_validated_at')->nullable();

            $table->string('mentor2_status', 20)->default('pending');
            $table->text('mentor2_note')->nullable();
            $table->foreignId('mentor2_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('mentor2_validated_at')->nullable();

            $table->text('kajur_note')->nullable();
            $table->foreignId('kajur_noted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('kajur_noted_at')->nullable();

            $table->string('wakil_status', 20)->default('pending');
            $table->text('wakil_note')->nullable();
            $table->foreignId('wakil_validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('wakil_validated_at')->nullable();

            $table->string('final_attendance_status', 20)->default('pending');
            $table->timestamps();

            $table->unique(['student_user_id', 'guidance_date'], 'uq_student_guidance_date');
            $table->index(['guidance_date', 'final_attendance_status'], 'idx_guidance_status_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_guidance_notes');
    }
};

