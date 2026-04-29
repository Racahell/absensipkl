<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\WorkflowState;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function attendanceCsv(): StreamedResponse
    {
        $filename = 'rekap-absensi-'.now()->format('Ymd-His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Tanggal', 'NIS', 'Nama', 'Role', 'Kategori', 'Status', 'Lokasi', 'Catatan']);

            Attendance::with(['user', 'location'])
                ->orderByDesc('attendance_date')
                ->limit(1000)
                ->get()
                ->each(function (Attendance $a) use ($out): void {
                    $recapStatus = WorkflowState::isAttendanceAlphaRecap($a->status) ? 'alpha' : $a->status;
                    fputcsv($out, [
                        optional($a->attendance_date)->format('Y-m-d'),
                        $a->user?->nis,
                        $a->user?->name,
                        $a->user?->role,
                        'absensi',
                        $recapStatus,
                        $a->location?->name,
                        $a->kajur_note ?: ($a->instruktur_note ?: $a->pembimbing_note),
                    ]);
                });

            LeaveRequest::with(['user', 'location'])
                ->orderByDesc('request_date')
                ->limit(1000)
                ->get()
                ->each(function (LeaveRequest $l) use ($out): void {
                    $recapStatus = WorkflowState::isLeaveAlphaRecap($l->status) ? 'alpha' : $l->status;
                    fputcsv($out, [
                        optional($l->request_date)->format('Y-m-d'),
                        $l->user?->nis,
                        $l->user?->name,
                        $l->user?->role,
                        'pengajuan_'.$l->type,
                        $recapStatus,
                        $l->location?->name,
                        $l->kajur_note ?: ($l->instruktur_note ?: $l->pembimbing_note),
                    ]);
                });

            fclose($out);
        }, 200, $headers);
    }

    public function reportExcel(Request $request): Response
    {
        $period = $this->normalizePeriod($request->string('period', 'monthly')->toString());
        $weekStart = $this->resolveWeekStart($request);
        [$waliClassName, $departmentName, $className, $studentId] = $this->resolveRoleFilters($request);
        $rows = $this->buildRows($period, $weekStart, $waliClassName, $departmentName, $className, $studentId);
        $periodLabel = $this->periodLabel($period);
        $filename = 'laporan-kehadiran-'.now()->format('Ymd-His').'.xls';

        $html = view('reports.export-table', [
            'rows' => $rows,
            'periodLabel' => $periodLabel,
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'forExcel' => true,
        ])->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function reportPdf(Request $request): Response
    {
        $period = $this->normalizePeriod($request->string('period', 'monthly')->toString());
        $weekStart = $this->resolveWeekStart($request);
        [$waliClassName, $departmentName, $className, $studentId] = $this->resolveRoleFilters($request);
        $rows = $this->buildRows($period, $weekStart, $waliClassName, $departmentName, $className, $studentId);
        $filename = 'laporan-kehadiran-'.now()->format('Ymd-His').'.pdf';

        $pdf = Pdf::loadView('reports.pdf-formal', [
            'rows' => $rows,
            'periodLabel' => $this->periodLabel($period),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'mode' => 'pdf',
        ])->setPaper('a4', 'portrait');

        return $pdf->download($filename);
    }

    public function reportPrint(Request $request): Response
    {
        $period = $this->normalizePeriod($request->string('period', 'monthly')->toString());
        $weekStart = $this->resolveWeekStart($request);
        [$waliClassName, $departmentName, $className, $studentId] = $this->resolveRoleFilters($request);
        $rows = $this->buildRows($period, $weekStart, $waliClassName, $departmentName, $className, $studentId);

        return response()->view('reports.pdf-formal', [
            'rows' => $rows,
            'periodLabel' => $this->periodLabel($period),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
            'mode' => 'print',
        ]);
    }

    private function normalizePeriod(string $period): string
    {
        return in_array($period, ['weekly', 'monthly', 'yearly'], true) ? $period : 'monthly';
    }

    private function periodLabel(string $period): string
    {
        return match ($period) {
            'weekly' => 'Mingguan',
            'yearly' => 'Tahunan',
            default => 'Bulanan',
        };
    }

    /**
     * @return array{0:?string,1:?string,2:?string,3:?int}
     */
    private function resolveRoleFilters(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            return [null, null, null, null];
        }

        $waliClassName = null;
        $departmentName = null;
        $className = null;
        $studentId = null;

        if ($user->role === 'wali_kelas') {
            $waliClassName = trim((string) ($user->class_name ?? ''));
        }

        if ($user->role === 'kesiswaan') {
            $requested = trim((string) $request->string('jurusan')->toString());
            $allowed = User::query()
                ->where('role', 'siswa')
                ->whereNotNull('department_name')
                ->where('department_name', '!=', '')
                ->distinct()
                ->pluck('department_name')
                ->all();

            $departmentName = ($requested !== '' && in_array($requested, $allowed, true))
                ? $requested
                : '__empty__';
            $requestedClass = trim((string) $request->string('kelas')->toString());
            if ($departmentName !== '__empty__' && $requestedClass !== '') {
                $classAllowed = User::query()
                    ->where('role', 'siswa')
                    ->where('department_name', $departmentName)
                    ->whereNotNull('class_name')
                    ->where('class_name', '!=', '')
                    ->distinct()
                    ->pluck('class_name')
                    ->all();
                $className = in_array($requestedClass, $classAllowed, true) ? $requestedClass : null;
            }
        }

        if (in_array((string) $user->role, ['kajur', 'instruktur'], true)) {
            $departmentName = trim((string) ($user->department_name ?? ''));
            if ($departmentName === '') {
                $departmentName = '__empty__';
            }
            $requestedClass = trim((string) $request->string('kelas')->toString());
            if ($departmentName !== '__empty__' && $requestedClass !== '') {
                $classAllowed = User::query()
                    ->where('role', 'siswa')
                    ->where('department_name', $departmentName)
                    ->whereNotNull('class_name')
                    ->where('class_name', '!=', '')
                    ->distinct()
                    ->pluck('class_name')
                    ->all();
                $className = in_array($requestedClass, $classAllowed, true) ? $requestedClass : null;
            }
        }

        $requestedStudentId = (int) $request->integer('siswa');
        if ($requestedStudentId > 0) {
            $studentQuery = User::query()
                ->where('role', 'siswa')
                ->where('id', $requestedStudentId);

            if ($waliClassName !== null) {
                $studentQuery->where('class_name', $waliClassName);
            }
            if ($departmentName !== null && $departmentName !== '__empty__') {
                $studentQuery->where('department_name', $departmentName);
            }
            if ($className !== null) {
                $studentQuery->where('class_name', $className);
            }

            $studentId = $studentQuery->exists() ? $requestedStudentId : null;
        }

        return [$waliClassName, $departmentName, $className, $studentId];
    }

    private function resolveWeekStart(Request $request): ?Carbon
    {
        $value = trim((string) $request->string('week_start')->toString());
        if ($value === '') {
            return null;
        }
        return Carbon::parse($value)->startOfWeek(Carbon::MONDAY);
    }

    private function buildLabels(string $period, ?Carbon $weekStart = null): array
    {
        $labels = [];
        $now = Carbon::now();

        if ($period === 'weekly') {
            $baseWeekStart = ($weekStart ?? $now)->copy()->startOfWeek(Carbon::MONDAY);
            for ($i = 0; $i < 7; $i++) {
                $date = $baseWeekStart->copy()->addDays($i);
                $labels[$date->format('Y-m-d')] = ['start' => $date->toDateString(), 'end' => $date->toDateString()];
            }

            return $labels;
        }

        if ($period === 'monthly') {
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();

            while ($start->lte($end)) {
                $labels[$start->format('Y-m-d')] = ['start' => $start->toDateString(), 'end' => $start->toDateString()];
                $start->addDay();
            }

            return $labels;
        }

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create($now->year, $month, 1);
            $labels[$date->format('M')] = ['start' => $date->copy()->startOfMonth()->toDateString(), 'end' => $date->copy()->endOfMonth()->toDateString()];
        }

        return $labels;
    }

    private function buildRows(
        string $period,
        ?Carbon $weekStart = null,
        ?string $waliClassName = null,
        ?string $departmentName = null,
        ?string $className = null,
        ?int $studentId = null
    ): array {
        $labels = $this->buildLabels($period, $weekStart);
        $rows = [];

        foreach ($labels as $label => $range) {
            $att = Attendance::query()->whereBetween('attendance_date', [$range['start'], $range['end']]);
            $lv = LeaveRequest::query()->whereBetween('request_date', [$range['start'], $range['end']]);

            if ($waliClassName !== null) {
                if ($waliClassName === '') {
                    $att->whereRaw('1 = 0');
                    $lv->whereRaw('1 = 0');
                } else {
                    $att->whereHas('user', fn ($query) => $query->where('class_name', $waliClassName));
                    $lv->whereHas('user', fn ($query) => $query->where('class_name', $waliClassName));
                }
            }

            if ($departmentName !== null) {
                if ($departmentName === '__empty__') {
                    $att->whereRaw('1 = 0');
                    $lv->whereRaw('1 = 0');
                } else {
                    $att->whereHas('user', fn ($query) => $query->where('department_name', $departmentName));
                    $lv->whereHas('user', fn ($query) => $query->where('department_name', $departmentName));
                }
            }
            if ($className !== null) {
                $att->whereHas('user', fn ($query) => $query->where('class_name', $className));
                $lv->whereHas('user', fn ($query) => $query->where('class_name', $className));
            }
            if ($studentId !== null && $studentId > 0) {
                $att->where('user_id', $studentId);
                $lv->where('user_id', $studentId);
            }

            $hadir = (clone $att)->where('status', 'hadir')->count();
            $izin = (clone $lv)->where('status', 'izin_approved')->count();
            $sakit = (clone $lv)->where('status', 'sakit_approved')->count();
            $alpha = (clone $att)->where(function ($query): void {
                $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
            })->count() + (clone $lv)->where(function ($query): void {
                $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::LEAVE_REJECTS);
            })->count();
            $pending = (clone $att)->whereIn('status', WorkflowState::ATTENDANCE_PENDING)->count()
                + (clone $lv)->whereIn('status', WorkflowState::LEAVE_PENDING)->count();

            $rows[] = [
                'label' => $label,
                'hadir' => $hadir,
                'izin' => $izin,
                'sakit' => $sakit,
                'alpha' => $alpha,
                'pending' => $pending,
                'total' => $hadir + $izin + $sakit + $alpha + $pending,
            ];
        }

        return $rows;
    }
}
