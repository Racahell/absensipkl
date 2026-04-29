<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendances', 'check_in_location_label')) {
                $table->string('check_in_location_label')->nullable()->after('check_in_ip');
            }
            if (! Schema::hasColumn('attendances', 'check_in_location_address')) {
                $table->text('check_in_location_address')->nullable()->after('check_in_location_label');
            }
            if (! Schema::hasColumn('attendances', 'check_out_location_label')) {
                $table->string('check_out_location_label')->nullable()->after('check_out_ip');
            }
            if (! Schema::hasColumn('attendances', 'check_out_location_address')) {
                $table->text('check_out_location_address')->nullable()->after('check_out_location_label');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table): void {
            $columns = [
                'check_in_location_label',
                'check_in_location_address',
                'check_out_location_label',
                'check_out_location_address',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('attendances', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

