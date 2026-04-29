<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Assessment;
use App\Models\StatusLog;
use App\Support\DiscordNotifier;
use App\Support\StudentMentorScopeResolver;
use App\Support\ValidationLogger;
use App\Support\WorkflowState;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceValidationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeValidatorRole($request);

        $allowedBuckets = [
            'pending_checkin',
            'approved_checkin',
            'rejected_checkin',
            'pending_checkout',
            'approved_checkout',
            'rejected_checkout',
        ];
        $bucket = trim((string) $request->string('bucket', 'pending_checkin')->toString());
        if (! in_array($bucket, $allowedBuckets, true)) {
            $bucket = 'pending_checkin';
        }

        $keyword = strtolower(trim((string) $request->string('q')->toString()));
        $dateFrom = trim((string) $request->string('date_from')->toString());
        $dateTo = trim((string) $request->string('date_to')->toString());
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = Attendance::with(['user', 'report', 'location', 'assessment'])
            ->orderByDesc('attendance_date')
            ->orderByDesc('id');

        $this->applyActorScope($query, $request);

        $allItems = $query->get()
            ->unique(fn (Attendance $item) => $item->user_id.'|'.$item->attendance_date?->toDateString())
            ->values();

        foreach ($allItems as $item) {
            $this->syncEscalation($item);
        }

        $filtered = $allItems->filter(function (Attendance $item) use ($keyword, $dateFrom, $dateTo): bool {
            $attendanceDate = optional($item->attendance_date)?->toDateString() ?? '';
            $haystack = strtolower(implode(' ', [
                (string) ($item->user?->name ?? ''),
                (string) ($item->user?->nis ?? ''),
                (string) ($item->user?->nuptk ?? ''),
                (string) ($item->user?->email ?? ''),
            ]));

            $matchKeyword = $keyword === '' || str_contains($haystack, $keyword);
            $matchFrom = $dateFrom === '' || $attendanceDate >= $dateFrom;
            $matchTo = $dateTo === '' || $attendanceDate <= $dateTo;

            return $matchKeyword && $matchFrom && $matchTo;
        })->values();

        $filtered = $filtered->map(function (Attendance $item): Attendance {
            $item->setAttribute('checkin_stage_status', $this->resolveStageStatus($item, 'checkin'));
            $item->setAttribute('checkout_stage_status', $this->resolveStageStatus($item, 'checkout'));
            return $item;
        })->values();

        $pendingCheckinItems = $filtered
            ->filter(fn (Attendance $item): bool => $item->getAttribute('checkin_stage_status') === 'pending')
            ->values();
        $approvedCheckinItems = $filtered
            ->filter(fn (Attendance $item): bool => $item->getAttribute('checkin_stage_status') === 'approved')
            ->values();
        $rejectedCheckinItems = $filtered
            ->filter(fn (Attendance $item): bool => $item->getAttribute('checkin_stage_status') === 'rejected')
            ->values();

        $pendingCheckoutItems = $filtered
            ->filter(fn (Attendance $item): bool => $item->getAttribute('checkout_stage_status') === 'pending')
            ->values();
        $approvedCheckoutItems = $filtered
            ->filter(fn (Attendance $item): bool => $item->getAttribute('checkout_stage_status') === 'approved')
            ->values();
        $rejectedCheckoutItems = $filtered
            ->filter(fn (Attendance $item): bool => $item->getAttribute('checkout_stage_status') === 'rejected')
            ->values();

        $bucketItems = match ($bucket) {
            'approved_checkin' => $approvedCheckinItems,
            'rejected_checkin' => $rejectedCheckinItems,
            'pending_checkout' => $pendingCheckoutItems,
            'approved_checkout' => $approvedCheckoutItems,
            'rejected_checkout' => $rejectedCheckoutItems,
            default => $pendingCheckinItems,
        };

        $attendances = $this->paginateCollection($bucketItems, $perPage, $request);

        return view('attendances.validation', [
            'attendances' => $attendances,
            'role' => $request->user()->role,
            'bucket' => $bucket,
            'bucketCounts' => [
                'pending_checkin' => $pendingCheckinItems->count(),
                'approved_checkin' => $approvedCheckinItems->count(),
                'rejected_checkin' => $rejectedCheckinItems->count(),
                'pending_checkout' => $pendingCheckoutItems->count(),
                'approved_checkout' => $approvedCheckoutItems->count(),
                'rejected_checkout' => $rejectedCheckoutItems->count(),
            ],
            'filters' => [
                'q' => $request->string('q')->toString(),
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'per_page' => $perPage,
                'per_page_options' => $allowedPerPage,
                'bucket' => $bucket,
            ],
        ]);
    }

    public function approve(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorizeValidatorRole($request);

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
            'validation_stage' => ['required', 'in:checkin,checkout'],
            'senyum_baik' => ['nullable', 'boolean'],
            'keramahan_baik' => ['nullable', 'boolean'],
            'penampilan_baik' => ['nullable', 'boolean'],
            'komunikasi_baik' => ['nullable', 'boolean'],
            'realisasi_kerja_baik' => ['nullable', 'boolean'],
        ]);
        $stage = (string) $validated['validation_stage'];
        $this->authorizeAttendance($attendance, $request, $stage);

        DB::transaction(function () use ($attendance, $request, $validated, $stage): void {
            $fromStatus = $attendance->status;
            $fromValidationStatus = $attendance->validation_status;
            $checkinStatus = $this->resolveStageStatus($attendance, 'checkin');
            $checkoutStatus = $this->resolveStageStatus($attendance, 'checkout');

            if ($stage === 'checkin') {
                $checkinStatus = 'approved';
                if ($attendance->check_out_at !== null && $checkoutStatus !== 'rejected') {
                    $checkoutStatus = 'pending';
                }
            } else {
                $checkoutStatus = 'approved';
            }

            [$nextStatus, $nextValidationStatus, $nextSlaDueAt] = $this->composeOverallAttendanceState(
                $checkinStatus,
                $checkoutStatus,
                $attendance
            );

            $attendance->update([
                'status' => $nextStatus,
                'validation_status' => $nextValidationStatus,
                'checkin_validation_status' => $checkinStatus,
                'checkout_validation_status' => $checkoutStatus,
                'pembimbing_note' => $validated['note'] ?? null,
                'validated_by_pembimbing' => $request->user()->id,
                'validated_pembimbing_at' => now(),
                'reject_reason_code' => null,
                'validation_sla_due_at' => $nextSlaDueAt,
                'validation_escalated_at' => null,
                'validation_escalation_level' => null,
            ]);

            StatusLog::create([
                'attendance_id' => $attendance->id,
                'actor_user_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => $nextStatus,
                'note' => $validated['note'] ?? 'Disetujui',
            ]);

            if (
                $validated['validation_stage'] === 'checkout'
                && in_array($request->user()->role, ['pembimbing_pkl', 'superadmin'], true)
            ) {
                $senyumBaik = (bool) ($validated['senyum_baik'] ?? true);
                $keramahanBaik = (bool) ($validated['keramahan_baik'] ?? true);
                $penampilanBaik = (bool) ($validated['penampilan_baik'] ?? true);
                $komunikasiBaik = (bool) ($validated['komunikasi_baik'] ?? true);
                $realisasiKerjaBaik = (bool) ($validated['realisasi_kerja_baik'] ?? true);
                Assessment::updateOrCreate(
                    ['attendance_id' => $attendance->id],
                    [
                        'assessor_user_id' => $request->user()->id,
                        'senyum_baik' => $senyumBaik,
                        'senyum' => $senyumBaik ? 'baik' : 'kurang',
                        'keramahan_baik' => $keramahanBaik,
                        'keramahan' => $keramahanBaik ? 'baik' : 'kurang',
                        'penampilan_baik' => $penampilanBaik,
                        'penampilan' => $penampilanBaik ? 'baik' : 'kurang',
                        'komunikasi_baik' => $komunikasiBaik,
                        'komunikasi' => $komunikasiBaik ? 'baik' : 'kurang',
                        'realisasi_kerja_baik' => $realisasiKerjaBaik,
                        'realisasi_kerja' => $realisasiKerjaBaik ? 'baik' : 'kurang',
                        'note' => $validated['note'] ?? null,
                    ]
                );

                $dailyReport = $attendance->report()->first();
                if ($dailyReport && (string) $dailyReport->review_status === 'pending_pembimbing') {
                    $dailyReport->update([
                        // Saat checkout disetujui pembimbing, laporan harian ikut dianggap selesai.
                        'review_status' => 'reviewed_instruktur',
                        'pembimbing_review_note' => filled($validated['note'] ?? null)
                            ? $validated['note']
                            : $dailyReport->pembimbing_review_note,
                        'reviewed_by_pembimbing' => $request->user()->id,
                        'reviewed_pembimbing_at' => now(),
                        'reject_reason_code' => null,
                        'review_sla_due_at' => null,
                        'review_escalated_at' => null,
                        'review_escalation_level' => null,
                    ]);

                    ValidationLogger::log(
                        $request->user(),
                        'daily_report',
                        (int) $dailyReport->id,
                        'approve_laporan_via_checkout',
                        $validated['note'] ?? null,
                        [
                            'from_review_status' => 'pending_pembimbing',
                            'to_review_status' => 'reviewed_instruktur',
                            'attendance_id' => (int) $attendance->id,
                            'source' => 'attendance_checkout_approve',
                        ]
                    );
                }
            }

            ValidationLogger::log(
                $request->user(),
                'attendance',
                (int) $attendance->id,
                $stage === 'checkin' ? 'approve_checkin' : 'approve_checkout',
                $validated['note'] ?? null,
                [
                    'from_status' => $fromStatus,
                    'to_status' => $nextStatus,
                    'validation_stage' => $stage,
                    'checkin_stage_status' => $checkinStatus,
                    'checkout_stage_status' => $checkoutStatus,
                    'from_validation_status' => $fromValidationStatus,
                    'to_validation_status' => $nextValidationStatus,
                ]
            );
        });

        return back()->with('success', 'Absensi berhasil di-approve.');
    }

    public function reject(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorizeValidatorRole($request);

        $validated = $request->validate([
            'validation_stage' => ['required', 'in:checkin,checkout'],
            'note' => ['required', 'string', 'max:1000'],
            'reject_reason_code' => ['required', 'in:reject_checkin,reject_checkout'],
        ]);
        $stage = (string) $validated['validation_stage'];
        $this->authorizeAttendance($attendance, $request, $stage);

        if (($stage === 'checkin' && $validated['reject_reason_code'] !== 'reject_checkin')
            || ($stage === 'checkout' && $validated['reject_reason_code'] !== 'reject_checkout')) {
            return back()->withErrors(['reject_reason_code' => 'Reason code reject tidak sesuai dengan tahap validasi.']);
        }

        DB::transaction(function () use ($attendance, $request, $validated, $stage): void {
            $fromStatus = $attendance->status;
            $fromValidationStatus = $attendance->validation_status;
            $checkinStatus = $this->resolveStageStatus($attendance, 'checkin');
            $checkoutStatus = $this->resolveStageStatus($attendance, 'checkout');
            if ($stage === 'checkin') {
                $checkinStatus = 'rejected';
            } else {
                $checkoutStatus = 'rejected';
            }
            $attendance->update([
                'status' => 'alpha',
                'validation_status' => 'rejected_pembimbing',
                'checkin_validation_status' => $checkinStatus,
                'checkout_validation_status' => $checkoutStatus,
                'reject_reason_code' => $validated['reject_reason_code'],
                'pembimbing_note' => $validated['note'],
                'validated_by_pembimbing' => $request->user()->id,
                'validated_pembimbing_at' => now(),
                'validation_sla_due_at' => null,
                'validation_escalated_at' => null,
            ]);

            StatusLog::create([
                'attendance_id' => $attendance->id,
                'actor_user_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => 'alpha',
                'note' => $validated['note'],
            ]);

            ValidationLogger::log(
                $request->user(),
                'attendance',
                (int) $attendance->id,
                $stage === 'checkin' ? 'reject_checkin' : 'reject_checkout',
                $validated['note'],
                [
                    'from_status' => $fromStatus,
                    'to_status' => 'alpha',
                    'validation_stage' => $stage,
                    'checkin_stage_status' => $checkinStatus,
                    'checkout_stage_status' => $checkoutStatus,
                    'reject_reason_code' => $validated['reject_reason_code'],
                    'from_validation_status' => $fromValidationStatus,
                    'to_validation_status' => 'rejected_pembimbing',
                ]
            );
        });

        return back()->with('success', 'Absensi ditolak. Status berubah menjadi Alpha.');
    }

    public function saveNote(Request $request, Attendance $attendance): RedirectResponse
    {
        $this->authorizeValidatorRole($request);

        $user = $request->user();
        $role = (string) ($user->role ?? '');
        if (! in_array($role, ['instruktur', 'kajur', 'superadmin'], true)) {
            abort(403, 'Hanya instruktur/kajur yang dapat menambahkan catatan tahap lanjut.');
        }

        $validated = $request->validate([
            'validation_stage' => ['required', 'in:checkin,checkout'],
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $stage = (string) $validated['validation_stage'];
        if ($stage === 'checkout' && $attendance->check_out_at === null) {
            return back()->withErrors(['attendance' => 'Data check-out belum tersedia.']);
        }

        $stageStatus = $this->resolveStageStatus($attendance, $stage);
        if (! in_array($stageStatus, ['approved', 'rejected'], true)) {
            return back()->withErrors([
                'attendance' => 'Catatan instruktur/kajur dapat ditambahkan setelah tahap ini diproses pembimbing.',
            ]);
        }

        DB::transaction(function () use ($attendance, $validated, $stage, $role, $user): void {
            $note = trim((string) $validated['note']);
            $now = now();
            $fromStatus = (string) ($attendance->status ?? 'pending');

            if ($role === 'kajur') {
                $attendance->update([
                    'kajur_note' => $note,
                    'validated_by_kajur' => $user->id,
                    'validated_kajur_at' => $now,
                ]);
            } else {
                $attendance->update([
                    'instruktur_note' => $note,
                    'validated_by_instruktur' => $user->id,
                    'validated_instruktur_at' => $now,
                ]);
            }

            StatusLog::create([
                'attendance_id' => $attendance->id,
                'actor_user_id' => $user->id,
                'from_status' => $fromStatus,
                'to_status' => $fromStatus,
                'note' => $note,
            ]);

            ValidationLogger::log(
                $user,
                'attendance',
                (int) $attendance->id,
                $role === 'kajur' ? 'add_note_kajur' : 'add_note_instruktur',
                $note,
                [
                    'validation_stage' => $stage,
                    'stage_status' => $stageStatus,
                ]
            );
        });

        return back()->with('success', 'Catatan berhasil disimpan.');
    }

    private function authorizeAttendance(Attendance $attendance, Request $request, string $stage): void
    {
        $stageStatus = $this->resolveStageStatus($attendance, $stage);
        abort_if($stageStatus !== 'pending', 422, 'Status absensi pada tahap ini tidak dalam kondisi pending.');
        if ($stage === 'checkout') {
            abort_if($attendance->check_out_at === null, 422, 'Data check-out belum tersedia.');
        }

        if (! $this->canAccessStudent($request, $attendance->user)) {
            abort(403, 'Tidak berwenang validasi absensi ini.');
        }
    }

    private function authorizeValidatorRole(Request $request): void
    {
        // Access is controlled by menu.permission middleware + menu permissions matrix.
        // Do not block by fixed role here.
    }

    private function syncEscalation(Attendance $attendance): void
    {
        $checkinStatus = $this->resolveStageStatus($attendance, 'checkin');
        $checkoutStatus = $this->resolveStageStatus($attendance, 'checkout');
        $hasPendingStage = $checkinStatus === 'pending' || $checkoutStatus === 'pending';

        if (! $hasPendingStage) {
            return;
        }

        if (! $attendance->validation_sla_due_at) {
            $attendance->update(['validation_sla_due_at' => WorkflowState::defaultSlaDueAt()]);
            return;
        }

        $dueAt = Carbon::parse($attendance->validation_sla_due_at);
        $level = WorkflowState::escalationLevel($dueAt);

        if ($level && $attendance->validation_escalated_at === null) {
            $attendance->update([
                'validation_escalated_at' => now(),
                'validation_escalation_level' => $level,
            ]);

            DiscordNotifier::notifyEditDelete('SLA Escalation - Validasi Absensi', [
                'Attendance ID' => $attendance->id,
                'Siswa' => $attendance->user?->name ?? '-',
                'Status' => $attendance->status,
                'Escalation Level' => $level,
                'Due At' => (string) $attendance->validation_sla_due_at,
            ]);
        }
    }

    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->integer('page', 1));
        $total = $items->count();
        $offset = ($page - 1) * $perPage;
        $pageItems = $items->slice($offset, $perPage)->values();

        return new LengthAwarePaginator(
            $pageItems,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function resolveStageStatus(Attendance $attendance, string $stage): string
    {
        $column = $stage === 'checkout' ? 'checkout_validation_status' : 'checkin_validation_status';
        $raw = strtolower(trim((string) ($attendance->{$column} ?? '')));
        if (in_array($raw, ['pending', 'approved', 'rejected', 'not_submitted'], true)) {
            return $raw;
        }

        $status = strtolower(trim((string) ($attendance->status ?? '')));
        $validationStatus = strtolower(trim((string) ($attendance->validation_status ?? '')));
        $rejectReasonCode = strtolower(trim((string) ($attendance->reject_reason_code ?? '')));
        $hasCheckout = $attendance->check_out_at !== null;

        if ($stage === 'checkout' && ! $hasCheckout) {
            return 'not_submitted';
        }

        if ($stage === 'checkout') {
            if ($rejectReasonCode === 'reject_checkout') {
                return 'rejected';
            }
            if ($status === 'alpha' || in_array($validationStatus, ['rejected_pembimbing'], true)) {
                return 'rejected';
            }

            // Fallback aman: jika checkout sudah ada tapi belum ada kolom stage status,
            // treat as pending agar tidak otomatis hilang dari bucket pending checkout.
            return 'pending';
        }

        if ($rejectReasonCode === 'reject_'.$stage) {
            return 'rejected';
        }
        if (in_array($validationStatus, ['rejected_pembimbing'], true) || $status === 'alpha') {
            return 'rejected';
        }
        if (in_array($validationStatus, ['approved_pembimbing'], true) || $status === 'hadir') {
            return 'approved';
        }

        return 'pending';
    }

    /**
     * @return array{0:string,1:string,2:\Illuminate\Support\Carbon|null}
     */
    private function composeOverallAttendanceState(string $checkinStatus, string $checkoutStatus, Attendance $attendance): array
    {
        if ($checkinStatus === 'rejected' || $checkoutStatus === 'rejected') {
            return ['alpha', 'rejected_pembimbing', null];
        }

        $hasPending = $checkinStatus === 'pending' || $checkoutStatus === 'pending';
        if ($hasPending) {
            $dueAt = $attendance->validation_sla_due_at ? Carbon::parse($attendance->validation_sla_due_at) : WorkflowState::defaultSlaDueAt();
            return ['pending', 'pending', $dueAt];
        }

        if ($checkinStatus === 'approved' && in_array($checkoutStatus, ['approved', 'not_submitted'], true)) {
            $isFinalApproved = $checkoutStatus === 'approved' || $attendance->check_out_at === null;
            if ($isFinalApproved && $attendance->check_out_at !== null) {
                return ['hadir', 'approved_pembimbing', null];
            }

            return ['pending', 'pending', null];
        }

        return ['pending', 'pending', null];
    }

    private function applyActorScope($query, Request $request): void
    {
        $actor = $request->user();
        StudentMentorScopeResolver::applyStudentScope($query, $actor, 'user');
    }

    private function canAccessStudent(Request $request, $student): bool
    {
        return StudentMentorScopeResolver::canAccessStudent($request->user(), $student);
    }
}
