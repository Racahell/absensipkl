<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendances')
            ->where('status', 'pending_pembimbing')
            ->update(['status' => 'pending']);

        DB::table('attendances')
            ->where('validation_status', 'pending_pembimbing')
            ->update(['validation_status' => 'pending']);

        DB::statement("ALTER TABLE attendances MODIFY status VARCHAR(60) NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE attendances MODIFY validation_status VARCHAR(40) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::table('attendances')
            ->where('status', 'pending')
            ->update(['status' => 'pending_pembimbing']);

        DB::table('attendances')
            ->where('validation_status', 'pending')
            ->update(['validation_status' => 'pending_pembimbing']);

        DB::statement("ALTER TABLE attendances MODIFY status VARCHAR(60) NOT NULL DEFAULT 'pending_pembimbing'");
        DB::statement("ALTER TABLE attendances MODIFY validation_status VARCHAR(40) NOT NULL DEFAULT 'pending_pembimbing'");
    }
};

