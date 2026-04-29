<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('chatbot_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_token', 64);
            $table->string('role', 50);
            $table->string('lang', 8)->default('id');
            $table->boolean('is_bot')->default(false);
            $table->text('message');
            $table->string('intent_key', 80)->nullable();
            $table->decimal('confidence', 8, 4)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'session_token']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
    }
};

