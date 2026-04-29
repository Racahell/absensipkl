<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            if (! Schema::hasColumn('attendances', 'validation_status')) {
                $table->string('validation_status', 40)->default('pending_pembimbing')->after('status');
                $table->index(['validation_status', 'attendance_date'], 'attendances_validation_status_idx');
            }
        });

        DB::statement("
            UPDATE attendances
            SET validation_status = CASE
                WHEN status = 'pending_pembimbing' THEN 'pending_pembimbing'
                WHEN status = 'hadir' THEN 'approved_pembimbing'
                WHEN status = 'alpha' THEN 'rejected_pembimbing'
                ELSE 'pending_pembimbing'
            END
            WHERE validation_status IS NULL OR validation_status = ''
        ");

        Schema::table('daily_reports', function (Blueprint $table): void {
            if (! Schema::hasColumn('daily_reports', 'plan_items')) {
                $table->json('plan_items')->nullable()->after('plan_work');
            }
            if (! Schema::hasColumn('daily_reports', 'actual_items')) {
                $table->json('actual_items')->nullable()->after('actual_work');
            }
            if (! Schema::hasColumn('daily_reports', 'special_assignment')) {
                $table->text('special_assignment')->nullable()->after('assigned_task');
            }
            if (! Schema::hasColumn('daily_reports', 'review_note_instruktur')) {
                $table->text('review_note_instruktur')->nullable()->after('instruktur_review_note');
            }
        });

        Schema::table('assessments', function (Blueprint $table): void {
            if (! Schema::hasColumn('assessments', 'senyum')) {
                $table->string('senyum', 10)->nullable()->after('senyum_baik');
            }
            if (! Schema::hasColumn('assessments', 'keramahan')) {
                $table->string('keramahan', 10)->nullable()->after('keramahan_baik');
            }
            if (! Schema::hasColumn('assessments', 'penampilan')) {
                $table->string('penampilan', 10)->nullable()->after('penampilan_baik');
            }
            if (! Schema::hasColumn('assessments', 'komunikasi')) {
                $table->string('komunikasi', 10)->nullable()->after('komunikasi_baik');
            }
            if (! Schema::hasColumn('assessments', 'realisasi_kerja')) {
                $table->string('realisasi_kerja', 10)->nullable()->after('realisasi_kerja_baik');
            }
        });

        DB::statement("
            UPDATE assessments
            SET
                senyum = CASE WHEN senyum_baik = 1 THEN 'baik' ELSE 'kurang' END,
                keramahan = CASE WHEN keramahan_baik = 1 THEN 'baik' ELSE 'kurang' END,
                penampilan = CASE WHEN penampilan_baik = 1 THEN 'baik' ELSE 'kurang' END,
                komunikasi = CASE WHEN komunikasi_baik = 1 THEN 'baik' ELSE 'kurang' END,
                realisasi_kerja = CASE WHEN realisasi_kerja_baik = 1 THEN 'baik' ELSE 'kurang' END
        ");

        if (! Schema::hasTable('validation_logs')) {
            Schema::create('validation_logs', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('actor_role', 40)->nullable();
                $table->string('target_type', 80);
                $table->unsignedBigInteger('target_id');
                $table->string('action', 80);
                $table->text('note')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->index(['target_type', 'target_id'], 'validation_logs_target_idx');
                $table->index(['actor_role', 'created_at'], 'validation_logs_actor_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('validation_logs')) {
            Schema::dropIfExists('validation_logs');
        }

        Schema::table('assessments', function (Blueprint $table): void {
            foreach (['senyum', 'keramahan', 'penampilan', 'komunikasi', 'realisasi_kerja'] as $col) {
                if (Schema::hasColumn('assessments', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('daily_reports', function (Blueprint $table): void {
            foreach (['plan_items', 'actual_items', 'special_assignment', 'review_note_instruktur'] as $col) {
                if (Schema::hasColumn('daily_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('attendances', function (Blueprint $table): void {
            if (Schema::hasColumn('attendances', 'validation_status')) {
                $table->dropIndex('attendances_validation_status_idx');
                $table->dropColumn('validation_status');
            }
        });
    }
};

