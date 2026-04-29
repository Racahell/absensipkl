<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_user_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('senyum_baik')->default(true);
            $table->boolean('keramahan_baik')->default(true);
            $table->boolean('penampilan_baik')->default(true);
            $table->boolean('komunikasi_baik')->default(true);
            $table->boolean('realisasi_kerja_baik')->default(true);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique('attendance_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
