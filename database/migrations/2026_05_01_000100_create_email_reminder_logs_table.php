<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('email_reminder_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email', 255);
            $table->string('reminder_type', 50);
            $table->string('status', 20)->default('sent');
            $table->string('message', 500)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'reminder_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_reminder_logs');
    }
};

