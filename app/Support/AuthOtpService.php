<?php

namespace App\Support;

use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

class AuthOtpService
{
    public const PURPOSE_LOGIN = 'login_otp';
    public const PURPOSE_VERIFY_EMAIL = 'verify_email';
    public const PURPOSE_VERIFY_PHONE = 'verify_phone';

    /**
     * @return array{record:OtpCode,plain_code:string,expires_at:CarbonInterface}
     */
    public function issue(User $user, string $purpose, string $channel, ?string $target = null, int $minutes = 5): array
    {
        OtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $plainCode = (string) random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes($minutes);

        $record = OtpCode::query()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'channel' => $channel,
            'target' => $target,
            'code_hash' => Hash::make($plainCode),
            'attempt_count' => 0,
            'max_attempts' => 5,
            'expires_at' => $expiresAt,
        ]);

        return [
            'record' => $record,
            'plain_code' => $plainCode,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * @return array{ok:bool,reason:?string,record:?OtpCode}
     */
    public function verify(User $user, string $purpose, string $channel, string $code, ?string $target = null): array
    {
        $record = OtpCode::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->where('channel', $channel)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $record) {
            return ['ok' => false, 'reason' => 'not_found', 'record' => null];
        }
        if ($target !== null && trim((string) $record->target) !== trim($target)) {
            return ['ok' => false, 'reason' => 'target_mismatch', 'record' => $record];
        }
        if ($record->expires_at->isPast()) {
            return ['ok' => false, 'reason' => 'expired', 'record' => $record];
        }
        if ($record->attempt_count >= $record->max_attempts) {
            return ['ok' => false, 'reason' => 'max_attempts', 'record' => $record];
        }

        if (! Hash::check($code, $record->code_hash)) {
            $record->increment('attempt_count');
            return ['ok' => false, 'reason' => 'invalid', 'record' => $record->fresh()];
        }

        $record->update([
            'used_at' => now(),
            'attempt_count' => $record->attempt_count + 1,
        ]);

        return ['ok' => true, 'reason' => null, 'record' => $record->fresh()];
    }

    public function sendEmailOtp(User $user, string $targetEmail, string $code, string $purposeLabel, int $minutes = 5): void
    {
        Notification::route('mail', $targetEmail)
            ->notify(new OtpCodeNotification($code, $purposeLabel, $minutes));
    }

    public function sendPhoneOtp(User $user, string $phone, string $code, string $purposeLabel, int $minutes = 5): void
    {
        $message = "Kode OTP Absensi PKL ({$purposeLabel}): {$code}. Berlaku {$minutes} menit.";
        $webhook = config('services.whatsapp.webhook_url');

        if (! $webhook) {
            return;
        }

        Http::post($webhook, [
            'phone' => $phone,
            'message' => $message,
            'user_id' => $user->id,
        ]);
    }
}

