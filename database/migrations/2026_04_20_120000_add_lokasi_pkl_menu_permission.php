<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('menus')->updateOrInsert(
            ['key' => 'fitur/lokasi-pkl'],
            [
                'name' => 'Lokasi PKL',
                'url' => '/fitur/lokasi-pkl',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $menuId = DB::table('menus')->where('key', 'fitur/lokasi-pkl')->value('id');
        if (! $menuId) {
            return;
        }

        $roles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek'];
        foreach ($roles as $role) {
            DB::table('menu_permissions')->updateOrInsert(
                ['menu_id' => $menuId, 'role' => $role],
                ['is_allowed' => in_array($role, ['superadmin', 'admin_sekolah'], true)]
            );
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('key', 'fitur/lokasi-pkl')->value('id');
        if ($menuId) {
            DB::table('menu_permissions')->where('menu_id', $menuId)->delete();
            DB::table('menus')->where('id', $menuId)->delete();
        }
    }
};

