<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('website_settings')) {
            return;
        }

        Schema::create('website_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name')->default('Permata Harapan');
            $table->string('site_title')->default('Absensi & Monitoring PKL');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('address')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('contact')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
