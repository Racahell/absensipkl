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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pkl_location_id')->nullable()->constrained('pkl_locations')->nullOnDelete();
            $table->date('attendance_date');
            $table->timestamp('check_in_at');
            $table->decimal('check_in_latitude', 10, 7);
            $table->decimal('check_in_longitude', 10, 7);
            $table->string('check_in_ip', 45)->nullable();
            $table->string('check_in_selfie_path');
            $table->timestamp('check_out_at')->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();
            $table->string('check_out_ip', 45)->nullable();
            $table->text('check_out_summary')->nullable();
            $table->enum('status', ['pending_pembimbing', 'pending_instruktur', 'pending_kajur', 'hadir', 'alpha'])
                ->default('pending_pembimbing');
            $table->text('pembimbing_note')->nullable();
            $table->text('instruktur_note')->nullable();
            $table->text('kajur_note')->nullable();
            $table->foreignId('validated_by_pembimbing')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by_instruktur')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by_kajur')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_pembimbing_at')->nullable();
            $table->timestamp('validated_instruktur_at')->nullable();
            $table->timestamp('validated_kajur_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date']);
            $table->index(['status', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
