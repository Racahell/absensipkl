<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'role') && Schema::hasColumn('users', 'role_id')) {
            DB::statement("
                UPDATE users u
                LEFT JOIN roles r ON r.id = u.role_id
                SET u.role_id = r.id
                WHERE u.role_id IS NOT NULL
            ");

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('role');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 50)->nullable()->after('nuptk');
            });
        }

        if (Schema::hasColumn('users', 'role_id')) {
            DB::statement("
                UPDATE users u
                LEFT JOIN roles r ON r.id = u.role_id
                SET u.role = r.key
                WHERE u.role_id IS NOT NULL
            ");
        }
    }
};

