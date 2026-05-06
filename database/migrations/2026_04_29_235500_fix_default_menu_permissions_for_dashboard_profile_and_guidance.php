<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = ['admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek', 'wakil_kepsek'];
        $menuKeys = ['dashboard', 'profil', 'validasi/catatan-bimbingan', 'catatan-bimbingan', 'wakil-kepsek/validasi-kehadiran'];
        $menus = DB::table('menus')->whereIn('key', $menuKeys)->get(['id', 'key']);

        foreach ($menus as $menu) {
            foreach ($roles as $role) {
                $allow = in_array($menu->key, ['dashboard', 'profil'], true)
                    || ($menu->key === 'validasi/catatan-bimbingan' && $role === 'pembimbing_pkl')
                    || ($menu->key === 'catatan-bimbingan' && $role === 'siswa')
                    || ($menu->key === 'wakil-kepsek/validasi-kehadiran' && $role === 'wakil_kepsek');

                DB::table('menu_permissions')->updateOrInsert(
                    ['menu_id' => $menu->id, 'role' => $role],
                    ['is_allowed' => $allow]
                );
            }

            DB::table('menu_permissions')->updateOrInsert(
                ['menu_id' => $menu->id, 'role' => 'superadmin'],
                ['is_allowed' => true]
            );
        }
    }

    public function down(): void
    {
        // No-op: this migration enforces sane defaults for critical menus.
    }
};

