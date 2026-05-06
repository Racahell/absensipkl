<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $menus = [
            ['name' => 'Dashboard', 'url' => '/dashboard', 'key' => 'dashboard'],
            ['name' => 'Profil Saya', 'url' => '/profil', 'key' => 'profil'],
            ['name' => 'Catatan Bimbingan', 'url' => '/catatan-bimbingan', 'key' => 'catatan-bimbingan'],
            ['name' => 'Validasi Kehadiran', 'url' => '/wakil-kepsek/validasi-kehadiran', 'key' => 'wakil-kepsek/validasi-kehadiran'],
        ];

        foreach ($menus as $menu) {
            DB::table('menus')->updateOrInsert(['key' => $menu['key']], $menu);
        }

        $roles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek', 'wakil_kepsek'];
        $menuRows = DB::table('menus')->whereIn('key', array_column($menus, 'key'))->get(['id', 'key']);

        foreach ($menuRows as $menu) {
            foreach ($roles as $role) {
                $isAllowed = $role === 'superadmin'
                    || (in_array($menu->key, ['dashboard', 'profil'], true) && $role === 'wakil_kepsek')
                    || ($menu->key === 'catatan-bimbingan' && $role === 'siswa')
                    || ($menu->key === 'wakil-kepsek/validasi-kehadiran' && $role === 'wakil_kepsek');
                DB::table('menu_permissions')->updateOrInsert(
                    ['menu_id' => $menu->id, 'role' => $role],
                    ['is_allowed' => $isAllowed]
                );
            }
        }
    }

    public function down(): void
    {
        $keys = ['catatan-bimbingan', 'wakil-kepsek/validasi-kehadiran'];
        $menuIds = DB::table('menus')->whereIn('key', $keys)->pluck('id');
        DB::table('menu_permissions')->whereIn('menu_id', $menuIds)->delete();
        DB::table('menus')->whereIn('key', $keys)->delete();
    }
};
