<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = Carbon::now();

        $menuId = DB::table('menus')->where('key', 'kajur/penempatan-pkl')->value('id');
        if (! $menuId) {
            DB::table('menus')->insert([
                'name' => 'Penempatan PKL Siswa',
                'url' => '/kajur/penempatan-pkl',
                'key' => 'kajur/penempatan-pkl',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $menuId = DB::table('menus')->where('key', 'kajur/penempatan-pkl')->value('id');
        } else {
            DB::table('menus')
                ->where('id', $menuId)
                ->update([
                    'name' => 'Penempatan PKL Siswa',
                    'url' => '/kajur/penempatan-pkl',
                    'updated_at' => $now,
                ]);
        }

        if ($menuId) {
            $roles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek'];
            foreach ($roles as $role) {
                DB::table('menu_permissions')->updateOrInsert(
                    ['menu_id' => $menuId, 'role' => $role],
                    [
                        'is_allowed' => in_array($role, ['superadmin', 'kajur'], true),
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('key', 'kajur/penempatan-pkl')->value('id');
        if ($menuId) {
            DB::table('menu_permissions')->where('menu_id', $menuId)->delete();
            DB::table('menus')->where('id', $menuId)->delete();
        }
    }
};

