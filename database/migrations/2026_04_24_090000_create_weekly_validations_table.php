<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weekly_validations', function (Blueprint $table) {
            $table->id();
            $table->date('week_start');
            $table->date('week_end');
            $table->string('department_name', 100)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->string('status', 30)->default('pending');
            $table->text('note')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('approved_by_kajur')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['week_start', 'week_end'], 'weekly_validations_week_idx');
            $table->index(['department_name', 'class_name'], 'weekly_validations_scope_idx');
            $table->unique(['week_start', 'week_end', 'department_name', 'class_name'], 'weekly_validations_unique_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weekly_validations');
    }
};
