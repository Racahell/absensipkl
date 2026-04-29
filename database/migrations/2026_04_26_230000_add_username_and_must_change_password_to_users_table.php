<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username', 120)->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(true)->after('must_reset_password');
            }
        });

        $users = DB::table('users')->select(['id', 'username', 'nis', 'nuptk', 'email', 'must_reset_password'])->get();
        foreach ($users as $user) {
            $username = trim((string) ($user->username ?? ''));
            if ($username === '') {
                $username = trim((string) ($user->nis ?: ($user->nuptk ?: $user->email)));
            }

            if ($username === '') {
                $username = 'user'.$user->id;
            }

            $candidate = $username;
            $i = 1;
            while (DB::table('users')->where('username', $candidate)->where('id', '!=', $user->id)->exists()) {
                $i++;
                $candidate = $username.$i;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'username' => $candidate,
                    'must_change_password' => (bool) ($user->must_reset_password ?? true),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['username', 'must_change_password']);
        });
    }
};

