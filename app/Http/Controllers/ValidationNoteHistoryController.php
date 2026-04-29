<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\WeeklyValidation;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

class ValidationNoteHistoryController extends Controller
{
    public function index(Request $request): View
    {
        $userId = $request->user()?->id;

        $attendanceRows = Attendance::query()
            ->with(['report'])
            ->where('user_id', $userId)
            ->where(function ($query): void {
                $query
                    ->whereNotNull('pembimbing_note')
                    ->orWhereNotNull('instruktur_note')
                    ->orWhereNotNull('kajur_note')
                    ->orWhereHas('report', function ($reportQuery): void {
                        $reportQuery
                            ->whereNotNull('pembimbing_review_note')
                            ->orWhereNotNull('instruktur_review_note')
                            ->orWhereNotNull('review_note_instruktur')
                            ->orWhereNotNull('kajur_review_note');
                    });
            })
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get();

        $attendanceNotes = $attendanceRows
            ->map(function (Attendance $attendance): array {
                $report = $attendance->report;

                $pembimbingNote = collect([
                    $attendance->pembimbing_note,
                    $report?->pembimbing_review_note,
                ])->filter(fn ($value): bool => filled($value))
                    ->map(fn ($value): string => trim((string) $value))
                    ->unique()
                    ->implode(' | ');

                $instrukturNote = collect([
                    $attendance->instruktur_note,
                    $report?->instruktur_review_note,
                    $report?->review_note_instruktur,
                ])->filter(fn ($value): bool => filled($value))
                    ->map(fn ($value): string => trim((string) $value))
                    ->unique()
                    ->implode(' | ');

                $kajurNote = collect([
                    $attendance->kajur_note,
                    $report?->kajur_review_note,
                ])->filter(fn ($value): bool => filled($value))
                    ->map(fn ($value): string => trim((string) $value))
                    ->unique()
                    ->implode(' | ');

                [$notes, $noteCount] = $this->buildRoleNotes($pembimbingNote, $instrukturNote, $kajurNote);

                return [
                    'type' => 'Absensi / Laporan Harian',
                    'date' => optional($attendance->attendance_date)?->toDateString() ?? '-',
                    'week_start' => optional($attendance->attendance_date)?->copy()?->startOfWeek(Carbon::MONDAY)?->toDateString(),
                    'week_end' => optional($attendance->attendance_date)?->copy()?->endOfWeek(Carbon::SUNDAY)?->toDateString(),
                    'status' => $this->composeAttendanceReportStatus($attendance),
                    'notes' => $notes,
                    'notes_count' => $noteCount,
                ];
            })
            ->groupBy(function (array $row): string {
                return (string) ($row['week_start'] ?? $row['date'] ?? '-');
            })
            ->map(function ($group): array {
                $items = collect($group)->values();
                $first = (array) $items->first();

                $roles = ['Pembimbing Sekolah', 'Instruktur', 'Kajur'];
                $roleNotes = [];
                foreach ($roles as $role) {
                    $merged = $items
                        ->flatMap(function (array $item) use ($role) {
                            return collect($item['notes'] ?? [])
                                ->filter(fn (array $note): bool => (string) ($note['role'] ?? '') === $role)
                                ->pluck('value')
                                ->all();
                        })
                        ->filter(fn ($value): bool => filled($value) && (string) $value !== '-')
                        ->map(fn ($value): string => trim((string) $value))
                        ->unique()
                        ->values();

                    $roleNotes[] = [
                        'role' => $role,
                        'value' => $merged->isEmpty() ? '-' : $merged->implode(' | '),
                    ];
                }

                $weekStart = (string) ($first['week_start'] ?? '');
                $weekEnd = (string) ($first['week_end'] ?? '');
                $displayDate = ($weekStart !== '' && $weekEnd !== '')
                    ? $weekStart.' s/d '.$weekEnd
                    : (string) ($first['date'] ?? '-');

                $statusSummary = $items->pluck('status')
                    ->filter(fn ($value): bool => filled($value))
                    ->map(fn ($value): string => trim((string) $value))
                    ->unique()
                    ->values()
                    ->take(3)
                    ->implode(' | ');

                $noteCount = collect($roleNotes)
                    ->filter(fn (array $note): bool => (string) ($note['value'] ?? '-') !== '-')
                    ->count();

                return [
                    'type' => 'Absensi / Laporan Harian (Mingguan)',
                    'date' => $displayDate,
                    'status' => $statusSummary !== '' ? $statusSummary : '-',
                    'notes' => $roleNotes,
                    'notes_count' => $noteCount,
                ];
            })
            ->values();

        $leaveNotes = LeaveRequest::query()
            ->with('user:id,department_name,class_name')
            ->where('user_id', $userId)
            ->where(function ($query): void {
                $query
                    ->whereNotNull('pembimbing_note')
                    ->orWhereNotNull('instruktur_note')
                    ->orWhereNotNull('kajur_note');
            })
            ->orderByDesc('request_date')
            ->get()
            ->map(function (LeaveRequest $leave): array {
                $weekStart = optional($leave->request_date)?->copy()?->startOfWeek(Carbon::MONDAY);
                $weekEnd = optional($leave->request_date)?->copy()?->endOfWeek(Carbon::SUNDAY);
                $weekly = null;
                if ($weekStart && $weekEnd) {
                    $department = trim((string) ($leave->user?->department_name ?? ''));
                    $className = trim((string) ($leave->user?->class_name ?? ''));

                    $weeklyQuery = WeeklyValidation::query()
                        ->whereDate('week_start', $weekStart->toDateString())
                        ->whereDate('week_end', $weekEnd->toDateString());

                    if ($department !== '') {
                        $weeklyQuery->where('department_name', $department);
                    } else {
                        $weeklyQuery->whereNull('department_name');
                    }

                    $weekly = (clone $weeklyQuery)
                        ->where('class_name', $className !== '' ? $className : null)
                        ->first();

                    if (! $weekly) {
                        $weekly = (clone $weeklyQuery)
                            ->whereNull('class_name')
                            ->first();
                    }
                }

                $instrukturNote = trim((string) ($leave->instruktur_note ?? ''));
                $kajurNote = trim((string) ($leave->kajur_note ?? ''));
                if ($instrukturNote === '' && $weekly && filled($weekly->instruktur_note)) {
                    $instrukturNote = trim((string) $weekly->instruktur_note);
                }
                if ($kajurNote === '' && $weekly && filled($weekly->kajur_note)) {
                    $kajurNote = trim((string) $weekly->kajur_note);
                }

                [$notes, $noteCount] = $this->buildRoleNotes(
                    $leave->pembimbing_note,
                    $instrukturNote,
                    $kajurNote
                );

                return [
                    'type' => 'Pengajuan Izin/Sakit',
                    'date' => optional($leave->request_date)?->toDateString() ?? '-',
                    'status' => $this->formatStatusLabel((string) $leave->status),
                    'notes' => $notes,
                    'notes_count' => $noteCount,
                ];
            });

        $allNotes = $attendanceNotes->concat($leaveNotes)
            ->sortByDesc('date')
            ->values();

        return view('notes.history', [
            'title' => 'Riwayat Catatan Pembimbing',
            'notes' => $allNotes,
        ]);
    }

    private function formatStatusLabel(string $raw): string
    {
        $raw = strtolower(trim($raw));
        return match (true) {
            $raw === 'pending_instruktur',
            $raw === 'pending_kajur',
            $raw === 'hadir',
            $raw === 'approved_final',
            str_starts_with($raw, 'approved'),
            str_starts_with($raw, 'reviewed_') => 'approved',
            str_starts_with($raw, 'pending') => 'pending',
            default => str_replace('_', ' ', $raw),
        };
    }

    private function composeAttendanceReportStatus(Attendance $attendance): string
    {
        $attendanceRaw = (string) ($attendance->validation_status ?: $attendance->status ?: '-');
        $attendanceStatus = $this->formatStatusLabel($attendanceRaw);

        $reportRaw = (string) ($attendance->report?->review_status ?? '');
        $reportStatus = $reportRaw !== '' ? $this->formatStatusLabel($reportRaw) : null;

        if ($attendanceStatus === 'approved' || $reportStatus === 'approved') {
            return 'approved';
        }

        if (
            str_contains($attendanceStatus, 'reject')
            || str_contains($attendanceStatus, 'alpha')
            || str_contains((string) $reportStatus, 'reject')
            || str_contains((string) $reportStatus, 'alpha')
            || str_contains((string) $reportStatus, 'revisi')
        ) {
            return 'rejected';
        }

        if ($reportStatus && $reportStatus !== '-') {
            return 'pending';
        }

        return $attendanceStatus;
    }

    /**
     * @return array{0:array<int, array{role:string,value:string}>,1:int}
     */
    private function buildRoleNotes(?string $pembimbing, ?string $instruktur, ?string $kajur): array
    {
        $rows = [
            ['role' => 'Pembimbing Sekolah', 'value' => trim((string) $pembimbing)],
            ['role' => 'Instruktur', 'value' => trim((string) $instruktur)],
            ['role' => 'Kajur', 'value' => trim((string) $kajur)],
        ];

        $count = collect($rows)->filter(fn (array $row): bool => $row['value'] !== '')->count();

        $notes = array_map(
            fn (array $row): array => [
                'role' => $row['role'],
                'value' => $row['value'] !== '' ? $row['value'] : '-',
            ],
            $rows
        );

        return [$notes, $count];
    }
}


