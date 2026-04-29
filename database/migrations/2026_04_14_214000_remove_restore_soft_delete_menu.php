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
            ->where('key', 'fitur/restore-soft-delete')
            ->delete();
    }

    public function down(): void
    {
        // intentionally no rollback for removed obsolete menu
    }
};

