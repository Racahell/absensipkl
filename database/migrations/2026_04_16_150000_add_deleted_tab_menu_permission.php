<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_permissions')) {
            return;
        }

        $menuId = DB::table('menus')->where('key', 'tab/deleted')->value('id');

        if (! $menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'name' => 'Tab Deleted (Global)',
                'url' => '/tab/deleted',
                'key' => 'tab/deleted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek'];

        foreach ($roles as $role) {
            DB::table('menu_permissions')->updateOrInsert(
                ['menu_id' => $menuId, 'role' => $role],
                [
                    'is_allowed' => $role === 'superadmin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_permissions')) {
            return;
        }

        $menuId = DB::table('menus')->where('key', 'tab/deleted')->value('id');

        if (! $menuId) {
            return;
        }

        DB::table('menu_permissions')->where('menu_id', $menuId)->delete();
        DB::table('menus')->where('id', $menuId)->delete();
    }
};
