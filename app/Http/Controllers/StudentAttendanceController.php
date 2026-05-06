<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\StatusLog;
use App\Support\AttendanceExceptionLogger;
use App\Support\LocationResolver;
use App\Support\ValidationLogger;
use App\Support\WorkflowState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StudentAttendanceController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('absensi.checkin.page');
    }

    public function checkInPage(Request $request): View
    {
        return view('attendances.student-checkin', $this->buildPageData($request));
    }

    public function checkOutPage(Request $request): View
    {
        return view('attendances.student-checkout', $this->buildPageData($request));
    }

    private function buildPageData(Request $request): array
    {
        $tz = $this->timezone();
        $today = now($tz)->toDateString();
        $yesterday = now($tz)->subDay();

        $todayAttendance = Attendance::with('report')
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $yesterdayNoCheckout = Attendance::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('attendance_date', $yesterday->toDateString())
            ->whereNull('check_out_at')
            ->first();

        // Sabtu & Minggu bersifat opsional, jadi tidak memicu warning kewajiban harian.
        if ($yesterdayNoCheckout && ! $this->isOptionalAttendanceDay($yesterday)) {
            AttendanceExceptionLogger::log(
                $request->user(),
                'no_checkout',
                'high',
                ['attendance_date' => (string) $yesterdayNoCheckout->attendance_date],
                $yesterdayNoCheckout
            );
        }

        return [
            'todayAttendance' => $todayAttendance,
            'timezone' => $tz,
            'checkinToken' => bin2hex(random_bytes(16)),
            'checkoutToken' => bin2hex(random_bytes(16)),
        ];
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tz = $this->timezone();
        $now = now($tz);
        $checkinToken = $request->string('request_token')->toString();

        if ($this->isDuplicateSubmit($user->id, 'checkin', $checkinToken)) {
            return back()->withErrors(['attendance' => 'Permintaan check-in terdeteksi duplikat. Silakan refresh lalu coba lagi.']);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'request_token' => ['required', 'string', 'min:10', 'max:100'],
        ]);

        $existing = Attendance::withTrashed()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();

        if ($existing && $existing->checkin_validation_status !== 'rejected') {
            return back()->withErrors([
                'attendance' => 'Check-in hari ini sudah ada.',
            ]);
        }

        $resolvedLocation = LocationResolver::reverseGeocode(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        try {
            DB::transaction(function () use ($user, $validated, $request, $now, $resolvedLocation, $existing): void {
                $checkInIp = $request->ip();
                $checkInDevice = $this->resolveDeviceLabel((string) $request->userAgent());
                $data = [
                    'user_id' => $user->id,
                    'pkl_location_id' => null,
                    'attendance_date' => $now->toDateString(),
                    'check_in_at' => $now,
                    'check_in_latitude' => $validated['latitude'],
                    'check_in_longitude' => $validated['longitude'],
                    'check_in_ip' => $checkInIp,
                    'check_in_device' => $checkInDevice,
                    'check_in_location_label' => $resolvedLocation['label'],
                    'check_in_location_address' => $resolvedLocation['address'],
                    'check_in_selfie_path' => 'no-photo',
                    'check_in_request_token' => $validated['request_token'],
                    'status' => 'pending',
                    'validation_status' => 'pending',
                    'checkin_validation_status' => 'pending',
                    'checkout_validation_status' => $existing ? $existing->checkout_validation_status : 'not_submitted',
                    'session_status' => 'open',
                    'validation_sla_due_at' => WorkflowState::defaultSlaDueAt(),
                    'reject_reason_code' => null,
                    'pembimbing_note' => null,
                    'instruktur_note' => null,
                    'kajur_note' => null,
                ];

                if ($existing) {
                    $existing->update($data);
                    $attendance = $existing;
                } else {
                    $attendance = Attendance::create($data);
                }

                StatusLog::create([
                    'attendance_id' => $attendance->id,
                    'actor_user_id' => $user->id,
                    'from_status' => $existing ? $existing->status : null,
                    'to_status' => 'pending',
                    'note' => $existing ? 'Check-in ulang (sebelumnya ditolak)' : 'Check-in dibuat siswa',
                ]);

                ValidationLogger::log(
                    $user,
                    'attendance',
                    (int) $attendance->id,
                    $existing ? 'resubmit_checkin' : 'create_checkin',
                    $existing ? 'Check-in ulang (sebelumnya ditolak)' : 'Check-in dibuat siswa',
                    ['validation_status' => 'pending']
                );
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                return back()->withErrors([
                    'attendance' => 'Check-in hari ini sudah ada.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('absensi.checkin.page')->with('success', 'Check-in berhasil. Lokasi dan IP tersimpan.');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $user = $request->user();
        $tz = $this->timezone();
        $now = now($tz);
        $checkoutToken = $request->string('request_token')->toString();

        if ($this->isDuplicateSubmit($user->id, 'checkout', $checkoutToken)) {
            return back()->withErrors(['attendance' => 'Permintaan check-out terdeteksi duplikat. Silakan refresh lalu coba lagi.']);
        }

        $attendance = Attendance::withTrashed()
            ->where('user_id', $user->id)
            ->whereDate('attendance_date', $now->toDateString())
            ->first();

        if (! $attendance) {
            AttendanceExceptionLogger::log($user, 'checkout_without_checkin', 'high');
            return back()->withErrors([
                'attendance' => 'Belum ada check-in hari ini.',
            ]);
        }

        if ($attendance->check_out_at && $attendance->checkout_validation_status !== 'rejected') {
            return back()->withErrors([
                'attendance' => 'Check-out hari ini sudah dilakukan.',
            ]);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'plan_work' => ['required', 'string', 'max:1000'],
            'actual_work' => ['required', 'string', 'max:1000'],
            'assigned_task' => ['nullable', 'string', 'max:1000'],
            'field_issue' => ['nullable', 'string', 'max:1000'],
            'remember_note' => ['nullable', 'string', 'max:1000'],
            'request_token' => ['required', 'string', 'min:10', 'max:100'],
        ]);

        if (trim((string) $validated['actual_work']) === '') {
            AttendanceExceptionLogger::log($user, 'daily_report_empty', 'medium', [], $attendance);
            return back()->withErrors([
                'actual_work' => 'Realisasi pekerjaan tidak boleh kosong.',
            ]);
        }

        $resolvedLocation = LocationResolver::reverseGeocode(
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        DB::transaction(function () use ($attendance, $validated, $request, $user, $now, $resolvedLocation): void {
            $lockedAttendance = Attendance::withTrashed()
                ->whereKey($attendance->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedAttendance) {
                throw ValidationException::withMessages([
                    'attendance' => 'Data absensi tidak ditemukan.',
                ]);
            }

            if ($lockedAttendance->check_out_at && $lockedAttendance->checkout_validation_status !== 'rejected') {
                throw ValidationException::withMessages([
                    'attendance' => 'Check-out hari ini sudah dilakukan.',
                ]);
            }

            $isResubmit = $lockedAttendance->check_out_at !== null;
            $planItems = $this->toListItems((string) $validated['plan_work']);
            $actualItems = $this->toListItems((string) $validated['actual_work']);
            $lockedAttendance->update([
                'check_out_at' => $now,
                'check_out_latitude' => $validated['latitude'],
                'check_out_longitude' => $validated['longitude'],
                'check_out_ip' => $request->ip(),
                'check_out_device' => $this->resolveDeviceLabel((string) $request->userAgent()),
                'check_out_location_label' => $resolvedLocation['label'],
                'check_out_location_address' => $resolvedLocation['address'],
                'check_out_summary' => null,
                'check_out_request_token' => $validated['request_token'],
                'status' => 'pending',
                'validation_status' => 'pending',
                'checkout_validation_status' => 'pending',
                'session_status' => 'closed',
                'reject_reason_code' => null,
                'pembimbing_note' => null,
                'instruktur_note' => null,
                'kajur_note' => null,
            ]);

            $dailyReport = $lockedAttendance->report()->updateOrCreate(
                ['attendance_id' => $lockedAttendance->id],
                [
                    'plan_work' => $validated['plan_work'],
                    'plan_items' => $planItems,
                    'actual_work' => $validated['actual_work'],
                    'actual_items' => $actualItems,
                    'assigned_task' => $validated['assigned_task'] ?? null,
                    'special_assignment' => $validated['assigned_task'] ?? null,
                    'field_issue' => $validated['field_issue'] ?? null,
                    'remember_note' => $validated['remember_note'] ?? null,
                    'evidence_path' => $lockedAttendance->report?->evidence_path,
                    'review_status' => 'pending_pembimbing',
                    'review_sla_due_at' => WorkflowState::defaultSlaDueAt(),
                    'reject_reason_code' => null,
                    'pembimbing_review_note' => null,
                ]
            );

            StatusLog::create([
                'attendance_id' => $lockedAttendance->id,
                'actor_user_id' => $user->id,
                'from_status' => $lockedAttendance->status,
                'to_status' => 'pending',
                'note' => $isResubmit ? 'Check-out ulang (sebelumnya ditolak)' : 'Check-out dan daily report dikirim',
            ]);

            ValidationLogger::log(
                $user,
                'daily_report',
                (int) $dailyReport->id,
                $isResubmit ? 'resubmit_daily_report' : 'submit_daily_report',
                $isResubmit ? 'Check-out ulang (sebelumnya ditolak)' : 'Check-out dan daily report dikirim',
                [
                    'attendance_id' => $lockedAttendance->id,
                    'review_status' => 'pending_pembimbing',
                    'plan_items_count' => count($planItems),
                    'actual_items_count' => count($actualItems),
                ]
            );
        });

        return redirect()->route('absensi.checkout.page')->with('success', 'Check-out berhasil. Lokasi dan IP tersimpan.');
    }

    private function timezone(): string
    {
        return 'Asia/Jakarta';
    }

    private function isOptionalAttendanceDay(\Illuminate\Support\Carbon $date): bool
    {
        return in_array($date->dayOfWeekIso, [6, 7], true);
    }

    private function isDuplicateSubmit(int $userId, string $action, string $token): bool
    {
        if ($token === '') {
            return true;
        }

        $key = 'idempotency:'.$action.':'.$userId.':'.$token;
        return ! Cache::add($key, 1, now()->addMinutes(10));
    }

    /**
     * @return array<int, string>
     */
    private function toListItems(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $items = [];
        foreach ($lines as $line) {
            $clean = trim((string) $line);
            if ($clean === '') {
                continue;
            }
            $clean = preg_replace('/^\d+[\.\)]\s*/', '', $clean) ?: $clean;
            $items[] = $clean;
        }

        if ($items === [] && trim($text) !== '') {
            $items[] = trim($text);
        }

        return array_values(array_unique($items));
    }

    private function resolveDeviceLabel(string $userAgent): string
    {
        $ua = strtolower(trim($userAgent));
        if ($ua === '') {
            return 'Unknown Device';
        }

        $device = 'Desktop';
        if (str_contains($ua, 'iphone')) {
            $device = 'iPhone';
        } elseif (str_contains($ua, 'ipad')) {
            $device = 'iPad';
        } elseif (str_contains($ua, 'android')) {
            $device = str_contains($ua, 'mobile') ? 'Android Phone' : 'Android Tablet';
        } elseif (str_contains($ua, 'windows phone')) {
            $device = 'Windows Phone';
        } elseif (str_contains($ua, 'macintosh')) {
            $device = 'Mac';
        } elseif (str_contains($ua, 'windows')) {
            $device = 'Windows PC';
        } elseif (str_contains($ua, 'linux')) {
            $device = 'Linux PC';
        }

        $browser = 'Browser';
        if (str_contains($ua, 'edg/')) {
            $browser = 'Edge';
        } elseif (str_contains($ua, 'opr/') || str_contains($ua, 'opera')) {
            $browser = 'Opera';
        } elseif (str_contains($ua, 'chrome/')) {
            $browser = 'Chrome';
        } elseif (str_contains($ua, 'firefox/')) {
            $browser = 'Firefox';
        } elseif (str_contains($ua, 'safari/') && ! str_contains($ua, 'chrome/')) {
            $browser = 'Safari';
        }

        return $device.' - '.$browser;
    }
}
