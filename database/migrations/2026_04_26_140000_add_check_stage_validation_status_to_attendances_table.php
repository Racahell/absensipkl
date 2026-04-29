<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->string('checkin_validation_status', 20)->nullable()->after('validation_status');
            $table->string('checkout_validation_status', 20)->nullable()->after('checkin_validation_status');
            $table->index('checkin_validation_status');
            $table->index('checkout_validation_status');
        });

        DB::table('attendances')
            ->select(['id', 'status', 'validation_status', 'check_out_at', 'reject_reason_code'])
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $status = (string) ($row->status ?? '');
                    $validationStatus = (string) ($row->validation_status ?? '');
                    $rejectReasonCode = (string) ($row->reject_reason_code ?? '');
                    $hasCheckout = $row->check_out_at !== null;

                    $checkinStatus = 'pending';
                    if ($rejectReasonCode === 'reject_checkin') {
                        $checkinStatus = 'rejected';
                    } elseif (in_array($validationStatus, ['rejected_pembimbing'], true) || $status === 'alpha') {
                        $checkinStatus = 'rejected';
                    } elseif (in_array($validationStatus, ['approved_pembimbing'], true) || $status === 'hadir') {
                        $checkinStatus = 'approved';
                    }

                    $checkoutStatus = $hasCheckout ? 'pending' : 'not_submitted';
                    if ($hasCheckout) {
                        if ($rejectReasonCode === 'reject_checkout') {
                            $checkoutStatus = 'rejected';
                        } elseif (in_array($validationStatus, ['rejected_pembimbing'], true) || $status === 'alpha') {
                            $checkoutStatus = 'rejected';
                        } elseif (in_array($validationStatus, ['approved_pembimbing'], true) || $status === 'hadir') {
                            $checkoutStatus = 'approved';
                        }
                    }

                    DB::table('attendances')
                        ->where('id', $row->id)
                        ->update([
                            'checkin_validation_status' => $checkinStatus,
                            'checkout_validation_status' => $checkoutStatus,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex(['checkin_validation_status']);
            $table->dropIndex(['checkout_validation_status']);
            $table->dropColumn(['checkin_validation_status', 'checkout_validation_status']);
        });
    }
};

