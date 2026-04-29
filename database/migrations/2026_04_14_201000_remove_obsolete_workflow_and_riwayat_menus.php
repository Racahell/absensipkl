<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus')) {
            return;
        }

        DB::table('menus')
            ->whereIn('key', [
                'fitur/workflow-matrix',
                'fitur-admin/workflow-matrix',
                'fitur/riwayat-edit',
            ])->delete();
    }

    public function down(): void
    {
        // intentionally not restoring obsolete menus
    }
};

