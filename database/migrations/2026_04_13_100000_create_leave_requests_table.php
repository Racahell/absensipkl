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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pkl_location_id')->nullable()->constrained('pkl_locations')->nullOnDelete();
            $table->date('request_date');
            $table->enum('type', ['izin', 'sakit']);
            $table->text('reason');
            $table->string('evidence_path')->nullable();
            $table->enum('status', ['pending_pembimbing', 'pending_instruktur', 'pending_kajur', 'izin_approved', 'sakit_approved', 'alpha'])
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

            $table->unique(['user_id', 'request_date']);
            $table->index(['status', 'request_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
