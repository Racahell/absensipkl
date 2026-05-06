<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('daily_reports', 'remember_note')) {
            Schema::table('daily_reports', function (Blueprint $table): void {
                $table->text('remember_note')->nullable()->after('field_issue');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('daily_reports', 'remember_note')) {
            Schema::table('daily_reports', function (Blueprint $table): void {
                $table->dropColumn('remember_note');
            });
        }
    }
};
