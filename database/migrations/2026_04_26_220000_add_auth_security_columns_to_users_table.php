<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'google_id')) {
                $table->string('google_id')->nullable()->unique()->after('phone');
            }
            if (! Schema::hasColumn('users', 'is_google_linked')) {
                $table->boolean('is_google_linked')->default(false)->after('google_id');
            }
            if (! Schema::hasColumn('users', 'is_otp_active')) {
                $table->boolean('is_otp_active')->default(false)->after('is_google_linked');
            }
            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('email_verified_at');
            }
            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'last_login_ip')) {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'google_id',
                'is_google_linked',
                'is_otp_active',
                'phone_verified_at',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};

