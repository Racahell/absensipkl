<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\UsernameResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentImportController extends Controller
{
    private const DEFAULT_PASSWORD = '123456';

    public function create(): View
    {
        return view('students.import', [
            'defaultPassword' => self::DEFAULT_PASSWORD,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = fopen($validated['csv_file']->getRealPath(), 'r');
        if (! $file) {
            return back()->withErrors(['csv_file' => 'File CSV tidak bisa dibaca.']);
        }

        $header = fgetcsv($file);
        if (! $header) {
            fclose($file);
            return back()->withErrors(['csv_file' => 'Header CSV tidak ditemukan.']);
        }

        $normalizedHeader = collect($header)
            ->map(function ($item): string {
                $value = Str::of((string) $item)->trim()->lower()->toString();
                return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
            })
            ->values();

        $nameColumn = $normalizedHeader->contains('name') ? 'name' : 'nama';
        $requiredColumns = ['nis', $nameColumn];
        foreach ($requiredColumns as $column) {
            if (! $normalizedHeader->contains($column)) {
                fclose($file);
                return back()->withErrors([
                    'csv_file' => "Kolom '{$column}' wajib ada di CSV.",
                ]);
            }
        }

        $nisIndex = $normalizedHeader->search('nis');
        $nameIndex = $normalizedHeader->search($nameColumn);

        $created = 0;
        $updated = 0;
        $line = 1;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($file)) !== false) {
                $line++;
                $nis = trim((string) ($row[$nisIndex] ?? ''));
                $name = trim((string) ($row[$nameIndex] ?? ''));

                if ($nis === '' && $name === '') {
                    continue;
                }

                if ($nis === '' || $name === '') {
                    throw new \RuntimeException("Baris {$line}: kolom nis dan name wajib diisi.");
                }

                $student = User::where('nis', $nis)->first();
                $email = $student?->email ?: strtolower($nis).'@siswa.local';

                if ($student) {
                    $student->update([
                        'name' => $name,
                        'username' => app(UsernameResolver::class)->generateUnique((string) ($student->username ?? ''), $nis, null, $email, (int) $student->id),
                        'role' => 'siswa',
                        'email' => $email,
                        'password' => Hash::make(self::DEFAULT_PASSWORD),
                        'must_reset_password' => true,
                        'must_change_password' => true,
                        'email_verified_at' => now(),
                        'phone_verified_at' => null,
                        'is_google_linked' => false,
                        'is_otp_active' => false,
                    ]);
                    $updated++;
                    continue;
                }

                User::create([
                    'name' => $name,
                    'username' => app(UsernameResolver::class)->generateUnique(null, $nis, null, $email),
                    'nis' => $nis,
                    'role' => 'siswa',
                    'email' => $email,
                    'created_by' => $request->user()->id,
                    'password' => Hash::make(self::DEFAULT_PASSWORD),
                    'must_reset_password' => true,
                    'must_change_password' => true,
                    'email_verified_at' => now(),
                    'phone_verified_at' => null,
                    'is_google_linked' => false,
                    'is_otp_active' => false,
                ]);
                $created++;
            }

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();
            fclose($file);

            return back()->withErrors([
                'csv_file' => 'Import gagal: '.$exception->getMessage(),
            ]);
        }

        fclose($file);

        return back()->with('success', "Import selesai. Baru: {$created}, diperbarui: {$updated}. Password default: ".self::DEFAULT_PASSWORD.'. Semua siswa wajib reset saat login pertama.');
    }
}
