<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WaPasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendEmailResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return back()->with('success', 'Link reset password berhasil dikirim ke email.');
    }

    public function sendWhatsappCode(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $phone = (string) $request->input('phone');

        $user = User::query()->where('phone', $phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => 'Nomor WhatsApp tidak ditemukan.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        WaPasswordReset::query()->create([
            'phone' => $phone,
            'code' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $message = "Kode reset password Anda: {$code}. Berlaku 15 menit.";
        $webhook = config('services.whatsapp.webhook_url');

        if ($webhook) {
            Http::post($webhook, [
                'phone' => $user->phone,
                'message' => $message,
            ]);
        }

        return redirect()->route('password.whatsapp.form')->with('success', $webhook ? 'Kode reset dikirim ke WhatsApp.' : 'Mode simulasi aktif. Kode reset: '.$code);
    }

    public function whatsappForm(): View
    {
        return view('auth.reset-password-wa');
    }

    public function resetWithWhatsapp(Request $request): RedirectResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'max:10'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $phone = (string) $request->input('phone');

        $record = WaPasswordReset::query()
            ->where('phone', $phone)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $record || $record->expires_at->isPast() || ! Hash::check((string) $request->input('code'), $record->code)) {
            throw ValidationException::withMessages([
                'code' => 'Kode WA tidak valid atau sudah kadaluarsa.',
            ]);
        }

        $user = User::query()->where('phone', $phone)->firstOrFail();
        $user->update([
            'password' => Hash::make((string) $request->input('password')),
        ]);

        $record->update(['used_at' => now()]);

        return redirect()->route('login')->with('success', 'Password berhasil diperbarui. Silakan login.');
    }
}
