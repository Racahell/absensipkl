<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendances', 'check_in_device')) {
                $table->string('check_in_device', 120)->nullable()->after('check_in_ip');
            }
            if (! Schema::hasColumn('attendances', 'check_out_device')) {
                $table->string('check_out_device', 120)->nullable()->after('check_out_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            foreach (['check_in_device', 'check_out_device'] as $col) {
                if (Schema::hasColumn('attendances', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
