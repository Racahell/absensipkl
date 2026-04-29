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

        $oldMenu = DB::table('menus')->where('key', 'fitur/manajemen-pengguna/deleted')->first();
        $newMenuId = DB::table('menus')->where('key', 'tab/deleted')->value('id');

        if (! $newMenuId) {
            $newMenuId = DB::table('menus')->insertGetId([
                'name' => 'Tab Deleted (Global)',
                'url' => '/tab/deleted',
                'key' => 'tab/deleted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek'];

        foreach ($roles as $role) {
            $oldAllowed = null;
            if ($oldMenu) {
                $oldAllowed = DB::table('menu_permissions')
                    ->where('menu_id', $oldMenu->id)
                    ->where('role', $role)
                    ->value('is_allowed');
            }

            DB::table('menu_permissions')->updateOrInsert(
                ['menu_id' => $newMenuId, 'role' => $role],
                [
                    'is_allowed' => $oldAllowed !== null ? (bool) $oldAllowed : $role === 'superadmin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if ($oldMenu) {
            DB::table('menu_permissions')->where('menu_id', $oldMenu->id)->delete();
            DB::table('menus')->where('id', $oldMenu->id)->delete();
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('menu_permissions')) {
            return;
        }

        $newMenuId = DB::table('menus')->where('key', 'tab/deleted')->value('id');

        if (! $newMenuId) {
            return;
        }

        $oldMenuId = DB::table('menus')->where('key', 'fitur/manajemen-pengguna/deleted')->value('id');
        if (! $oldMenuId) {
            $oldMenuId = DB::table('menus')->insertGetId([
                'name' => 'Tab Deleted (Manajemen Pengguna)',
                'url' => '/fitur/manajemen-pengguna/deleted',
                'key' => 'fitur/manajemen-pengguna/deleted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $rows = DB::table('menu_permissions')->where('menu_id', $newMenuId)->get();
        foreach ($rows as $row) {
            DB::table('menu_permissions')->updateOrInsert(
                ['menu_id' => $oldMenuId, 'role' => $row->role],
                [
                    'is_allowed' => (bool) $row->is_allowed,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        DB::table('menu_permissions')->where('menu_id', $newMenuId)->delete();
        DB::table('menus')->where('id', $newMenuId)->delete();
    }
};
