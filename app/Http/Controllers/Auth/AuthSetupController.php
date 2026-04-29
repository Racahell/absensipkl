<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAuditLogger;
use App\Support\AuthOtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;

class AuthSetupController extends Controller
{
    public function show(Request $request): View
    {
        return view('auth.setup', [
            'user' => $request->user(),
            'googleLinked' => (bool) $request->user()?->is_google_linked,
            'otpActive' => (bool) $request->user()?->is_otp_active,
        ]);
    }

    public function updateContact(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
        ]);

        $user->update([
            'email' => strtolower(trim((string) $data['email'])),
            'phone' => trim((string) $data['phone']),
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'is_otp_active' => false,
        ]);
        app(AuthAuditLogger::class)->log($user->id, 'auth.contact.updated', []);

        return back()->with('success', 'Kontak disimpan. Lanjutkan verifikasi email dan nomor HP.');
    }

    public function sendEmailOtp(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->email) {
            return back()->withErrors(['email' => 'Email belum diisi.']);
        }

        $throttleKey = 'auth-setup-email-otp:'.$user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return back()->withErrors(['email' => 'Terlalu banyak permintaan OTP email. Coba lagi sebentar.']);
        }
        RateLimiter::hit($throttleKey, 60);

        $issued = $otpService->issue($user, AuthOtpService::PURPOSE_VERIFY_EMAIL, 'email', (string) $user->email, 5);
        $otpService->sendEmailOtp($user, (string) $user->email, $issued['plain_code'], 'verifikasi email', 5);
        app(AuthAuditLogger::class)->log($user->id, 'auth.email_otp.sent', []);

        return back()->with('success', 'OTP email dikirim. Cek inbox Anda.');
    }

    public function verifyEmailOtp(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'email_otp' => ['required', 'digits:6'],
        ]);

        $result = $otpService->verify(
            $user,
            AuthOtpService::PURPOSE_VERIFY_EMAIL,
            'email',
            (string) $data['email_otp'],
            (string) $user->email
        );
        if (! $result['ok']) {
            app(AuthAuditLogger::class)->log($user->id, 'auth.email_otp.verify.failed', ['reason' => (string) ($result['reason'] ?? 'invalid')]);
            return back()->withErrors(['email_otp' => 'OTP email tidak valid/kadaluarsa.']);
        }

        $user->update(['email_verified_at' => now()]);
        app(AuthAuditLogger::class)->log($user->id, 'auth.email.verified', []);

        return back()->with('success', 'Email berhasil diverifikasi.');
    }

    public function sendPhoneOtp(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->phone) {
            return back()->withErrors(['phone' => 'Nomor HP belum diisi.']);
        }

        $throttleKey = 'auth-setup-phone-otp:'.$user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return back()->withErrors(['phone' => 'Terlalu banyak permintaan OTP HP. Coba lagi sebentar.']);
        }
        RateLimiter::hit($throttleKey, 60);

        $issued = $otpService->issue($user, AuthOtpService::PURPOSE_VERIFY_PHONE, 'phone', (string) $user->phone, 5);
        $otpService->sendPhoneOtp($user, (string) $user->phone, $issued['plain_code'], 'verifikasi nomor HP', 5);
        app(AuthAuditLogger::class)->log($user->id, 'auth.phone_otp.sent', []);

        // Fallback jika webhook WA tidak dikonfigurasi agar setup tetap berjalan saat development.
        if (! config('services.whatsapp.webhook_url')) {
            return back()->with('success', 'Mode simulasi aktif. OTP HP: '.$issued['plain_code']);
        }

        return back()->with('success', 'OTP nomor HP dikirim.');
    }

    public function verifyPhoneOtp(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'phone_otp' => ['required', 'digits:6'],
        ]);

        $result = $otpService->verify(
            $user,
            AuthOtpService::PURPOSE_VERIFY_PHONE,
            'phone',
            (string) $data['phone_otp'],
            (string) $user->phone
        );
        if (! $result['ok']) {
            app(AuthAuditLogger::class)->log($user->id, 'auth.phone_otp.verify.failed', ['reason' => (string) ($result['reason'] ?? 'invalid')]);
            return back()->withErrors(['phone_otp' => 'OTP nomor HP tidak valid/kadaluarsa.']);
        }

        $user->update(['phone_verified_at' => now()]);
        app(AuthAuditLogger::class)->log($user->id, 'auth.phone.verified', []);

        return back()->with('success', 'Nomor HP berhasil diverifikasi.');
    }

    public function toggleOtp(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $enable = $request->boolean('enable');

        if ($enable && (! $user->email_verified_at || ! $user->phone_verified_at)) {
            return back()->withErrors([
                'otp' => 'OTP login hanya bisa diaktifkan setelah email dan nomor HP terverifikasi.',
            ]);
        }

        $user->update(['is_otp_active' => $enable]);
        app(AuthAuditLogger::class)->log($user->id, $enable ? 'auth.otp_login.enabled' : 'auth.otp_login.disabled', []);

        return back()->with('success', $enable ? 'Login OTP diaktifkan.' : 'Login OTP dinonaktifkan.');
    }
}
