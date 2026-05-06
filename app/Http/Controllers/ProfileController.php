<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\VerifyPendingEmail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('users.profile', [
            'title' => 'Profil Saya',
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('users', 'pending_email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'photo' => ['nullable', 'image', 'max:4096'],
            'cropped_photo' => ['nullable', 'string'],
        ]);

        $newEmail = strtolower(trim((string) $data['email']));
        $currentEmail = strtolower(trim((string) $user->email));
        $emailChanged = $newEmail !== $currentEmail;
        $newPhone = trim((string) ($data['phone'] ?? ''));
        $currentPhone = trim((string) ($user->phone ?? ''));
        $phoneChanged = $newPhone !== $currentPhone;

        $payload = [
            'name' => $data['name'],
            'phone' => $data['phone'] ?: null,
            'pending_email' => $emailChanged ? $newEmail : null,
        ];

        if ($emailChanged) {
            $payload['is_otp_active'] = false;
        }

        if ($phoneChanged) {
            $payload['phone_verified_at'] = null;
            $payload['is_otp_active'] = false;
        }

        $newPhotoPath = $this->buildPhotoPath($request);
        if ($newPhotoPath !== null) {
            $this->deleteOldPhoto($user->profile_photo_path);
            $payload['profile_photo_path'] = $newPhotoPath;
        }

        $user->update($payload);

        if ($emailChanged) {
            try {
                Notification::route('mail', $newEmail)
                    ->notify(new VerifyPendingEmail($user, $newEmail));

                return back()->with('success', 'Profil berhasil diperbarui. Email baru belum aktif, silakan verifikasi dari inbox.');
            } catch (Throwable $e) {
                Log::error('Gagal mengirim email verifikasi setelah update profil.', [
                    'user_id' => $user->id,
                    'new_email' => $newEmail,
                    'error' => $e->getMessage(),
                ]);

                return back()->with('success', 'Profil berhasil diperbarui, tetapi email verifikasi gagal dikirim. Cek konfigurasi mail server.');
            }
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function verifyPendingEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = $request->user();
        if (! $user || $user->id !== $id) {
            abort(403);
        }

        $pendingEmailFromUrl = strtolower(trim((string) $request->query('email', '')));
        if ($pendingEmailFromUrl === '' || ! hash_equals($hash, sha1($pendingEmailFromUrl))) {
            abort(403);
        }

        $currentPendingEmail = strtolower(trim((string) $user->pending_email));
        if ($currentPendingEmail === '' || $currentPendingEmail !== $pendingEmailFromUrl) {
            return redirect()->route('profile.edit')
                ->withErrors(['email' => 'Verifikasi email tidak valid atau sudah tidak berlaku.']);
        }

        $usedByOtherUser = User::query()
            ->where('email', $pendingEmailFromUrl)
            ->whereKeyNot($user->id)
            ->exists();
        if ($usedByOtherUser) {
            return redirect()->route('profile.edit')
                ->withErrors(['email' => 'Email tersebut sudah digunakan akun lain.']);
        }

        $user->forceFill([
            'email' => $pendingEmailFromUrl,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        return redirect()->route('profile.edit')
            ->with('success', 'Email baru berhasil diverifikasi dan diaktifkan.');
    }

    public function sendResetPasswordEmail(Request $request): RedirectResponse
    {
        $email = (string) $request->user()->email;
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return back()->with('success', 'Link reset password berhasil dikirim ke email Anda.');
    }

    private function buildPhotoPath(Request $request): ?string
    {
        $croppedPhoto = (string) $request->input('cropped_photo', '');
        if ($croppedPhoto !== '') {
            return $this->storeCroppedBase64($request->user()->id, $croppedPhoto);
        }

        $photoFile = $request->file('photo');
        if (! $photoFile) {
            return null;
        }

        return $this->storeFromFile($request->user()->id, $photoFile->getRealPath());
    }

    private function storeCroppedBase64(int $userId, string $base64): string
    {
        if (! preg_match('/^data:image\/(png|jpe?g|webp);base64,/', $base64, $matches)) {
            abort(422, 'Format foto tidak valid.');
        }

        $raw = substr($base64, strpos($base64, ',') + 1);
        $binary = base64_decode($raw, true);

        if ($binary === false) {
            abort(422, 'Data foto tidak valid.');
        }

        $originalExtension = $this->normalizeImageExtension($matches[1]);
        if (! function_exists('imagecreatefromstring')) {
            $path = $this->makePhotoRelativePath($userId, $originalExtension);
            $fullPath = public_path($path);
            $this->ensurePhotoDirExists(dirname($fullPath));
            file_put_contents($fullPath, $binary);
            return $path;
        }

        $source = imagecreatefromstring($binary);
        if (! $source) {
            abort(422, 'Gagal memproses foto.');
        }

        $path = $this->makePhotoRelativePath($userId, 'png');
        $fullPath = public_path($path);
        $this->ensurePhotoDirExists(dirname($fullPath));
        imagealphablending($source, false);
        imagesavealpha($source, true);
        imagepng($source, $fullPath);
        imagedestroy($source);

        return $path;
    }

    private function storeFromFile(int $userId, string $realPath): string
    {
        $binary = file_get_contents($realPath);
        if ($binary === false) {
            abort(422, 'Gagal membaca file foto.');
        }

        if (! function_exists('imagecreatefromstring')) {
            $path = $this->makePhotoRelativePath($userId, $this->detectImageExtensionFromBinary($binary));
            $fullPath = public_path($path);
            $this->ensurePhotoDirExists(dirname($fullPath));
            file_put_contents($fullPath, $binary);

            return $path;
        }

        $source = imagecreatefromstring($binary);
        if (! $source) {
            abort(422, 'File foto tidak valid.');
        }

        $path = $this->makePhotoRelativePath($userId, 'png');
        $fullPath = public_path($path);
        $this->ensurePhotoDirExists(dirname($fullPath));
        imagealphablending($source, false);
        imagesavealpha($source, true);
        imagepng($source, $fullPath);

        imagedestroy($source);

        return $path;
    }

    private function detectImageExtensionFromBinary(string $binary): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_buffer($finfo, $binary);
                finfo_close($finfo);

                return match ($mime) {
                    'image/jpeg' => 'jpg',
                    'image/webp' => 'webp',
                    default => 'png',
                };
            }
        }

        return 'png';
    }

    private function normalizeImageExtension(string $extension): string
    {
        return strtolower($extension) === 'jpeg' ? 'jpg' : strtolower($extension);
    }

    private function makePhotoRelativePath(int $userId, string $extension): string
    {
        return 'uploads/profiles/'.$userId.'_'.Str::random(12).'.'.$extension;
    }

    private function ensurePhotoDirExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function deleteOldPhoto(?string $oldPath): void
    {
        if (! $oldPath || ! str_starts_with($oldPath, 'uploads/profiles/')) {
            return;
        }

        $fullPath = public_path($oldPath);
        if (is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
}
