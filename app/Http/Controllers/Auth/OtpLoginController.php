<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAuditLogger;
use App\Support\AuthOtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class OtpLoginController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.login-otp', [
            'contact' => trim((string) $request->query('contact', '')),
        ]);
    }

    public function send(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
        ]);

        $channel = 'email';
        $contact = trim((string) ($data['contact'] ?? ''));
        $identifier = trim((string) ($data['identifier'] ?? ''));

        if ($contact === '' && $identifier !== '') {
            $contact = $identifier;
        }
        if ($contact === '' || ! filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors([
                'contact' => 'Isi email dengan benar.',
            ])->withInput();
        }

        $contact = strtolower($contact);

        $throttleKey = 'otp-login-send:'.$request->ip().':'.$channel.':'.md5($contact);
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            return back()->withErrors([
                'contact' => 'Terlalu banyak permintaan OTP. Coba lagi sebentar.',
            ])->withInput();
        }
        RateLimiter::hit($throttleKey, 60);

        $user = $this->findActiveUserByChannelAndContact($channel, $contact);
        if (! $user) {
            app(AuthAuditLogger::class)->log(null, 'auth.otp.request.denied', [
                'channel' => $channel,
                'reason' => 'unknown_contact',
            ]);
        } else {
            $issued = $otpService->issue($user, AuthOtpService::PURPOSE_LOGIN, $channel, $contact, 5);
            if ($channel === 'email') {
                $otpService->sendEmailOtp($user, $contact, $issued['plain_code'], 'login', 5);
            } else {
                $otpService->sendPhoneOtp($user, $contact, $issued['plain_code'], 'login', 5);
            }
            app(AuthAuditLogger::class)->log($user->id, 'auth.otp.request.sent', ['channel' => $channel]);
        }

        return redirect()
            ->route('login.otp.form', ['channel' => $channel, 'contact' => $contact, 'sent' => 1])
            ->with('success', 'Jika kontak terdaftar dan aktif, OTP telah dikirim.');
    }

    public function verify(Request $request, AuthOtpService $otpService): RedirectResponse
    {
        $data = $request->validate([
            'channel' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:255'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'otp_code' => ['required', 'digits:6'],
        ]);

        $channel = 'email';
        $contact = trim((string) ($data['contact'] ?? ''));
        $identifier = trim((string) ($data['identifier'] ?? ''));
        if ($contact === '' && $identifier !== '') {
            $contact = $identifier;
        }
        if ($contact === '' || ! filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors([
                'otp_code' => 'Data verifikasi OTP tidak valid.',
            ])->withInput();
        }

        $contact = strtolower($contact);

        $user = $this->findActiveUserByChannelAndContact($channel, $contact);

        if (! $user) {
            app(AuthAuditLogger::class)->log(null, 'auth.otp.verify.failed', [
                'channel' => $channel,
                'reason' => 'unknown_contact',
            ]);
            return back()->withErrors([
                'otp_code' => 'Kode OTP tidak valid atau kadaluarsa.',
            ])->withInput();
        }

        $throttleKey = 'otp-login-verify:'.$request->ip().':'.$channel.':'.md5($contact);
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            app(AuthAuditLogger::class)->log($user->id, 'auth.otp.verify.rate_limited', ['channel' => $channel]);
            return back()->withErrors([
                'otp_code' => 'Terlalu banyak percobaan OTP. Coba lagi sebentar.',
            ])->withInput();
        }
        RateLimiter::hit($throttleKey, 60);

        $result = $otpService->verify(
            $user,
            AuthOtpService::PURPOSE_LOGIN,
            $channel,
            (string) $data['otp_code'],
            $contact
        );

        if (! $result['ok']) {
            app(AuthAuditLogger::class)->log($user->id, 'auth.otp.verify.failed', [
                'channel' => $channel,
                'reason' => (string) ($result['reason'] ?? 'invalid'),
            ]);
            return back()->withErrors([
                'otp_code' => 'OTP tidak valid atau sudah kadaluarsa.',
            ])->withInput();
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, false);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => (string) $request->ip(),
        ]);
        app(AuthAuditLogger::class)->log($user->id, 'auth.login.success', [
            'channel' => $channel,
            'method' => 'otp',
        ]);

        return redirect()->to($this->dashboardPathByRole((string) $user->role));
    }

    private function findActiveUserByChannelAndContact(string $channel, string $contact): ?User
    {
        $query = User::query()->where('is_deleted', false);
        return $query
            ->whereRaw('LOWER(email) = ?', [strtolower($contact)])
            ->whereNotNull('email_verified_at')
            ->first();
    }

    private function dashboardPathByRole(string $role): string
    {
        return match ($role) {
            'siswa' => '/dashboard/siswa',
            'pembimbing_pkl' => '/dashboard/pembimbing',
            'instruktur' => '/dashboard/instruktur',
            'wali_kelas' => '/dashboard/wali-kelas',
            'kajur' => '/dashboard/kajur',
            'kesiswaan' => '/dashboard/kesiswaan',
            'kepsek' => '/dashboard/kepsek',
            'admin_sekolah' => '/dashboard/admin-sekolah',
            'superadmin' => '/dashboard/superadmin',
            default => '/dashboard',
        };
    }
}
