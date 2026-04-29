<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $menuId = DB::table('menus')->where('key', 'chatbot')->value('id');

        if (! $menuId) {
            $menuId = DB::table('menus')->insertGetId([
                'key' => 'chatbot',
                'name' => 'Chatbot Asisten',
                'url' => '/chatbot',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $roles = ['superadmin', 'admin_sekolah', 'siswa', 'pembimbing_pkl', 'instruktur', 'kajur', 'wali_kelas', 'kesiswaan', 'kepsek'];
        foreach ($roles as $role) {
            DB::table('menu_permissions')->updateOrInsert(
                ['menu_id' => $menuId, 'role' => $role],
                ['is_allowed' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        $menuId = DB::table('menus')->where('key', 'chatbot')->value('id');
        if (! $menuId) {
            return;
        }

        DB::table('menu_permissions')->where('menu_id', $menuId)->delete();
        DB::table('menus')->where('id', $menuId)->delete();
    }
};

