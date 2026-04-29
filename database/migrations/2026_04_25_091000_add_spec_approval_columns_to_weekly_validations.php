<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('weekly_validations')) {
            return;
        }

        Schema::table('weekly_validations', function (Blueprint $table): void {
            if (! Schema::hasColumn('weekly_validations', 'approved_by_kajur')) {
                $table->foreignId('approved_by_kajur')->nullable()->after('validated_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('weekly_validations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by_kajur');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('weekly_validations')) {
            return;
        }

        Schema::table('weekly_validations', function (Blueprint $table): void {
            if (Schema::hasColumn('weekly_validations', 'approved_by_kajur')) {
                $table->dropConstrainedForeignId('approved_by_kajur');
            }
            if (Schema::hasColumn('weekly_validations', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
        });
    }
};

