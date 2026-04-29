<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menuId = DB::table('menus')->where('key', 'kajur/penempatan-pkl')->value('id');
        if (! $menuId) {
            return;
        }

        DB::table('menu_permissions')->where('menu_id', $menuId)->delete();
        DB::table('menus')->where('id', $menuId)->delete();
    }

    public function down(): void
    {
        // Intentionally left blank: this menu is deprecated and should stay removed.
    }
};

