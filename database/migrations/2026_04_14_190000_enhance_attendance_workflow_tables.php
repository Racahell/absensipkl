<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make status columns flexible for richer state machine.
        DB::statement("ALTER TABLE attendances MODIFY status VARCHAR(60) NOT NULL DEFAULT 'pending_pembimbing'");
        DB::statement("ALTER TABLE leave_requests MODIFY status VARCHAR(60) NOT NULL DEFAULT 'pending_pembimbing'");

        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('reject_reason_code', 80)->nullable()->after('status');
            $table->timestamp('validation_sla_due_at')->nullable()->after('validated_kajur_at');
            $table->timestamp('validation_escalated_at')->nullable()->after('validation_sla_due_at');
            $table->string('validation_escalation_level', 30)->nullable()->after('validation_escalated_at');
            $table->string('session_status', 30)->default('open')->after('check_out_summary');
            $table->string('check_in_request_token', 80)->nullable()->after('check_in_selfie_path');
            $table->string('check_out_request_token', 80)->nullable()->after('check_out_summary');
            $table->index(['validation_sla_due_at', 'status'], 'attendances_sla_status_idx');
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->string('reject_reason_code', 80)->nullable()->after('status');
            $table->timestamp('validation_sla_due_at')->nullable()->after('validated_kajur_at');
            $table->timestamp('validation_escalated_at')->nullable()->after('validation_sla_due_at');
            $table->string('validation_escalation_level', 30)->nullable()->after('validation_escalated_at');
            $table->index(['validation_sla_due_at', 'status'], 'leave_requests_sla_status_idx');
        });

        Schema::table('daily_reports', function (Blueprint $table): void {
            $table->string('review_status', 60)->default('pending_pembimbing')->after('evidence_path');
            $table->text('pembimbing_review_note')->nullable()->after('review_status');
            $table->text('instruktur_review_note')->nullable()->after('pembimbing_review_note');
            $table->text('kajur_review_note')->nullable()->after('instruktur_review_note');
            $table->string('reject_reason_code', 80)->nullable()->after('kajur_review_note');
            $table->foreignId('reviewed_by_pembimbing')->nullable()->after('reject_reason_code')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_instruktur')->nullable()->after('reviewed_by_pembimbing')->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by_kajur')->nullable()->after('reviewed_by_instruktur')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_pembimbing_at')->nullable()->after('reviewed_by_kajur');
            $table->timestamp('reviewed_instruktur_at')->nullable()->after('reviewed_pembimbing_at');
            $table->timestamp('reviewed_kajur_at')->nullable()->after('reviewed_instruktur_at');
            $table->timestamp('review_sla_due_at')->nullable()->after('reviewed_kajur_at');
            $table->timestamp('review_escalated_at')->nullable()->after('review_sla_due_at');
            $table->string('review_escalation_level', 30)->nullable()->after('review_escalated_at');
            $table->index(['review_status', 'review_sla_due_at'], 'daily_reports_review_sla_idx');
        });

        Schema::create('attendance_exceptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->date('event_date')->index();
            $table->string('exception_type', 80);
            $table->string('severity', 20)->default('medium');
            $table->json('meta')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['exception_type', 'event_date'], 'attendance_exceptions_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_exceptions');

        Schema::table('daily_reports', function (Blueprint $table): void {
            $table->dropIndex('daily_reports_review_sla_idx');
            $table->dropConstrainedForeignId('reviewed_by_pembimbing');
            $table->dropConstrainedForeignId('reviewed_by_instruktur');
            $table->dropConstrainedForeignId('reviewed_by_kajur');
            $table->dropColumn([
                'review_status',
                'pembimbing_review_note',
                'instruktur_review_note',
                'kajur_review_note',
                'reject_reason_code',
                'reviewed_pembimbing_at',
                'reviewed_instruktur_at',
                'reviewed_kajur_at',
                'review_sla_due_at',
                'review_escalated_at',
                'review_escalation_level',
            ]);
        });

        Schema::table('leave_requests', function (Blueprint $table): void {
            $table->dropIndex('leave_requests_sla_status_idx');
            $table->dropColumn([
                'reject_reason_code',
                'validation_sla_due_at',
                'validation_escalated_at',
                'validation_escalation_level',
            ]);
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex('attendances_sla_status_idx');
            $table->dropColumn([
                'reject_reason_code',
                'validation_sla_due_at',
                'validation_escalated_at',
                'validation_escalation_level',
                'session_status',
                'check_in_request_token',
                'check_out_request_token',
            ]);
        });
    }
};

