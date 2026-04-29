<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('role', 'owner')
            ->update(['role' => 'kepsek']);

        DB::table('users')
            ->where('role', 'operator')
            ->update(['role' => 'admin_sekolah']);

        $now = Carbon::now();
        $roleMap = [
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
        ];

        foreach ($roleMap as $fromRole => $toRole) {
            $items = DB::table('menu_permissions')
                ->where('role', $fromRole)
                ->get();

            foreach ($items as $item) {
                $existing = DB::table('menu_permissions')
                    ->where('menu_id', $item->menu_id)
                    ->where('role', $toRole)
                    ->first();

                if ($existing) {
                    $mergedAllowed = (bool) $existing->is_allowed || (bool) $item->is_allowed;

                    DB::table('menu_permissions')
                        ->where('id', $existing->id)
                        ->update([
                            'is_allowed' => $mergedAllowed,
                            'updated_at' => $now,
                        ]);

                    DB::table('menu_permissions')
                        ->where('id', $item->id)
                        ->delete();

                    continue;
                }

                DB::table('menu_permissions')
                    ->where('id', $item->id)
                    ->update([
                        'role' => $toRole,
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible safely: role lama sudah dipensiunkan dari sistem.
    }
};

