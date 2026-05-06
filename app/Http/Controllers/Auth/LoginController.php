<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthAuditLogger;
use App\Support\CaptchaService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class LoginController extends Controller
{
    private const INVALID_LOGIN_MESSAGE = 'NIS atau email atau password Anda salah.';

    public function create(Request $request): Response
    {
        // Prevent stale login pages from reusing expired CSRF tokens (419).
        $request->session()->regenerateToken();

        $captchaMode = CaptchaService::resolveMode();

        return response()->view('auth.login', [
            'captchaMode' => $captchaMode,
            'offlineQuestion' => $captchaMode === 'offline' ? CaptchaService::buildOfflineChallenge($request) : null,
            'recaptchaSiteKey' => config('services.recaptcha.site_key'),
            'googleOauthReady' => $this->isGoogleOauthReady(),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            if (! CaptchaService::validate($request)) {
                throw ValidationException::withMessages([
                    'captcha' => 'Verifikasi keamanan tidak valid.',
                ]);
            }

            $credentials = $request->validate([
                'identifier' => ['required', 'string', 'max:100'],
                'password' => ['required', 'string'],
            ]);

            $identifier = trim((string) $credentials['identifier']);
            $password = (string) $credentials['password'];
            $throttleKey = 'password-login:'.mb_strtolower($identifier).'|'.$request->ip();

            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                throw ValidationException::withMessages([
                    'identifier' => 'Terlalu banyak percobaan login. Coba lagi beberapa saat.',
                ]);
            }

            $candidates = $this->findActiveUserCandidatesByIdentifier($identifier);
            if ($candidates->count() > 1) {
                throw ValidationException::withMessages([
                    'identifier' => 'Identifier login bentrok di lebih dari satu akun. Hubungi admin untuk merapikan username/NIS/NUPTK.',
                ]);
            }
            $user = $candidates->first();

            if (! $user || ! Hash::check($password, $user->password)) {
                RateLimiter::hit($throttleKey, 60);
                app(AuthAuditLogger::class)->log($user?->id, 'auth.login.failed', [
                    'identifier' => $identifier,
                    'reason' => 'invalid_credentials',
                ]);
                throw ValidationException::withMessages([
                    'identifier' => self::INVALID_LOGIN_MESSAGE,
                ]);
            }

            RateLimiter::clear($throttleKey);
            Auth::login($user, $request->boolean('remember'));

            $request->session()->regenerate();

            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => (string) $request->ip(),
            ]);
            app(AuthAuditLogger::class)->log($user->id, 'auth.login.success', [
                'identifier' => $identifier,
                'method' => 'password',
            ]);

            return redirect()->to($this->dashboardPathByRole((string) $user->role));
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $loginErrorMessage = 'Layanan login sedang bermasalah. Silakan coba lagi.';
            if ($e instanceof QueryException && str_contains((string) $e->getMessage(), '[2002]')) {
                $loginErrorMessage = 'Database belum aktif. Silakan nyalakan MySQL lalu coba login lagi.';
            }

            Log::error('Login gagal diproses karena gangguan sistem.', [
                'error' => $e->getMessage(),
                'class' => $e::class,
            ]);

            throw ValidationException::withMessages([
                'identifier' => $loginErrorMessage,
            ]);
        }
    }

    public function destroy(Request $request): RedirectResponse
    {
        app(AuthAuditLogger::class)->log($request->user()?->id, 'auth.logout', []);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function findActiveUserCandidatesByIdentifier(string $identifier): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->where('is_deleted', false)
            ->where(function (Builder $query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier)
                    ->orWhere('nis', $identifier)
                    ->orWhere('nuptk', $identifier);
            })
            ->orderByRaw('CASE
                WHEN email = ? THEN 1
                WHEN username = ? THEN 2
                WHEN nis = ? THEN 3
                WHEN nuptk = ? THEN 4
                ELSE 99 END', [$identifier, $identifier, $identifier, $identifier])
            ->orderBy('id')
            ->get();
    }

    private function isGoogleOauthReady(): bool
    {
        return class_exists(\Laravel\Socialite\Facades\Socialite::class)
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
