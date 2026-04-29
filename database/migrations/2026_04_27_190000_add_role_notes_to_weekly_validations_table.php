<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weekly_validations', function (Blueprint $table): void {
            $table->text('instruktur_note')->nullable()->after('note');
            $table->text('kajur_note')->nullable()->after('instruktur_note');
            $table->foreignId('noted_by_instruktur')->nullable()->after('validated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('noted_instruktur_at')->nullable()->after('validated_at');
            $table->foreignId('noted_by_kajur')->nullable()->after('approved_by_kajur')->constrained('users')->nullOnDelete();
            $table->timestamp('noted_kajur_at')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('weekly_validations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('noted_by_instruktur');
            $table->dropConstrainedForeignId('noted_by_kajur');
            $table->dropColumn([
                'instruktur_note',
                'kajur_note',
                'noted_instruktur_at',
                'noted_kajur_at',
            ]);
        });
    }
};

