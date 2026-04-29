<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }

            if (! Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'deleted_by')) {
                $table->foreignId('deleted_by')->nullable()->after('must_reset_password')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'deleted_ip')) {
                $table->string('deleted_ip', 45)->nullable()->after('deleted_by');
            }

            if (! Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('email');
            }

            if (! Schema::hasColumn('users', 'menu_permissions')) {
                $table->json('menu_permissions')->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropColumn(['deleted_ip', 'phone_number', 'menu_permissions', 'deleted_at']);
        });
    }
};
