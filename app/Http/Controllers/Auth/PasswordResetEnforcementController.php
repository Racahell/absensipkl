<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuthAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordResetEnforcementController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.reset-password-wajib', [
            'mustReset' => (bool) $request->user()?->must_reset_password,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
            'must_reset_password' => false,
            'must_change_password' => false,
        ]);
        app(AuthAuditLogger::class)->log($user->id, 'auth.password.changed', ['mode' => 'forced_first_login']);

        return redirect()->route('dashboard')->with('success', 'Password berhasil diperbarui.');
    }
}
