<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirectForLogin(Request $request): RedirectResponse
    {
        if (! $this->isSocialiteReady()) {
            app(AuthAuditLogger::class)->log(null, 'auth.google.redirect.denied', ['reason' => 'not_configured', 'intent' => 'login']);
            return redirect()->route('login')->with('error', 'Login Google belum tersedia. Hubungi admin untuk konfigurasi OAuth Google.');
        }

        $request->session()->put('google_auth.intent', 'login');

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function redirectForLink(Request $request): RedirectResponse
    {
        if (! $this->isSocialiteReady()) {
            app(AuthAuditLogger::class)->log($request->user()?->id, 'auth.google.redirect.denied', ['reason' => 'not_configured', 'intent' => 'link']);
            return redirect()->route('auth.setup.show')->with('error', 'Link Google belum tersedia. Hubungi admin untuk konfigurasi OAuth Google.');
        }

        $request->session()->put('google_auth.intent', 'link');
        $request->session()->put('google_auth.link_user_id', (int) $request->user()->id);

        return Socialite::driver('google')
            ->scopes(['openid', 'email', 'profile'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! $this->isSocialiteReady()) {
            return redirect()->route('login')->with('error', 'Google OAuth tidak tersedia.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $e) {
            // Fallback for host/session mismatch (e.g. login from localhost, callback on 127.0.0.1).
            try {
                $googleUser = Socialite::driver('google')->stateless()->user();
                app(AuthAuditLogger::class)->log($request->user()?->id, 'auth.google.callback.stateless_fallback', [
                    'reason' => 'invalid_state',
                ]);
            } catch (Throwable $fallbackError) {
                Log::warning('Google OAuth callback gagal (invalid state + fallback gagal).', [
                    'error' => $fallbackError->getMessage(),
                    'class' => $fallbackError::class,
                ]);
                app(AuthAuditLogger::class)->log($request->user()?->id, 'auth.google.callback.failed', [
                    'reason' => 'invalid_state',
                    'fallback' => 'failed',
                ]);

                return redirect()->route('login')->with('error', 'Autentikasi Google gagal. Pastikan Anda menggunakan domain yang sama (localhost atau 127.0.0.1) dari awal sampai callback.');
            }
        } catch (Throwable $e) {
            Log::warning('Google OAuth callback gagal.', [
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);
            app(AuthAuditLogger::class)->log($request->user()?->id, 'auth.google.callback.failed', ['reason' => 'oauth_exception']);
            return redirect()->route('login')->with('error', 'Autentikasi Google gagal diproses.');
        }

        $intent = (string) $request->session()->pull('google_auth.intent', 'login');

        if ($intent === 'link') {
            $linkUserId = (int) $request->session()->pull('google_auth.link_user_id', 0);
            $authUser = $request->user();
            if (! $authUser || $authUser->id !== $linkUserId) {
                app(AuthAuditLogger::class)->log($request->user()?->id, 'auth.google.link.failed', ['reason' => 'invalid_session']);
                return redirect()->route('login')->with('error', 'Sesi linking Google tidak valid.');
            }

            $existing = User::query()
                ->where('google_id', (string) $googleUser->getId())
                ->where('id', '!=', $authUser->id)
                ->exists();
            if ($existing) {
                app(AuthAuditLogger::class)->log($authUser->id, 'auth.google.link.failed', ['reason' => 'already_linked_elsewhere']);
                return redirect()->route('auth.setup.show')->withErrors([
                    'google' => 'Akun Google ini sudah terhubung ke akun lain.',
                ]);
            }

            $authUser->update([
                'google_id' => (string) $googleUser->getId(),
                'is_google_linked' => true,
            ]);
            app(AuthAuditLogger::class)->log($authUser->id, 'auth.google.link.success', []);

            return redirect()->route('auth.setup.show')->with('success', 'Akun Google berhasil dihubungkan.');
        }

        if (! $googleUser->getEmail()) {
            app(AuthAuditLogger::class)->log(null, 'auth.google.login.denied', ['reason' => 'google_email_missing']);
            return redirect()->route('login')->withErrors([
                'identifier' => 'Email akun Google tidak ditemukan.',
            ]);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower((string) $googleUser->getEmail())])
            ->where('is_deleted', false)
            ->whereNotNull('email_verified_at')
            ->first();

        if (! $user) {
            app(AuthAuditLogger::class)->log(null, 'auth.google.login.denied', ['reason' => 'email_not_registered']);
            return redirect()->route('login')->withErrors([
                'identifier' => 'Akun Google ini belum terdaftar di sistem. Hubungi admin sekolah.',
            ]);
        }

        $user->update([
            'google_id' => (string) $googleUser->getId(),
            'is_google_linked' => true,
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => (string) $request->ip(),
        ]);
        app(AuthAuditLogger::class)->log($user->id, 'auth.login.success', ['method' => 'google']);

        return redirect()->to($this->dashboardPathByRole((string) $user->role));
    }

    public function unlink(Request $request): RedirectResponse
    {
        $request->user()->update([
            'google_id' => null,
            'is_google_linked' => false,
        ]);
        app(AuthAuditLogger::class)->log($request->user()?->id, 'auth.google.unlink.success', []);

        return back()->with('success', 'Akun Google berhasil dilepas.');
    }

    private function isSocialiteReady(): bool
    {
        return class_exists(Socialite::class)
            && filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'))
            && filled(config('services.google.redirect'));
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
