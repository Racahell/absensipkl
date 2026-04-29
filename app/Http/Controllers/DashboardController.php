<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceException;
use App\Models\DailyReport;
use App\Models\LeaveRequest;
use App\Models\PklLocation;
use App\Models\User;
use App\Models\WeeklyValidation;
use App\Support\WorkflowState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $activeRole = $user?->role;

        if (! $activeRole) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Role pengguna tidak valid.');
        }

        $dashboardRole = $this->normalizeDashboardRole($activeRole);
        $showAnalytics = in_array($dashboardRole, ['superadmin', 'admin_sekolah', 'kepsek', 'kesiswaan', 'instruktur'], true);

        $analyticsMode = $request->string('mode', 'daily')->toString();
        $chartType = $request->string('chart_type', 'bar')->toString();
        $analyticsRange = $request->string('range', $analyticsMode === 'monthly' ? 'this_month' : 'today')->toString();

        if (! in_array($analyticsMode, ['daily', 'monthly'], true)) {
            $analyticsMode = 'daily';
        }

        if (! in_array($chartType, ['bar', 'line', 'pie', 'doughnut'], true)) {
            $chartType = 'bar';
        }

        $allowedRanges = $analyticsMode === 'daily'
            ? ['today', 'yesterday']
            : ['this_month', 'last_month'];

        if (! in_array($analyticsRange, $allowedRanges, true)) {
            $analyticsRange = $allowedRanges[0];
        }

        $labels = [];
        $stats = [];
        $kpiCards = $this->buildKpiCards($dashboardRole, $user);
        $studentLocation = null;
        $selectedDepartment = $request->string('jurusan')->toString();
        $selectedClass = $request->string('kelas')->toString();
        $departmentOptions = [];
        $classOptions = [];
        $studentDashboard = null;

        if ($dashboardRole === 'siswa') {
            $studentLocation = $user?->pklLocation;
            $today = now()->toDateString();
            $todayAttendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('attendance_date', $today)
                ->latest('id')
                ->first();
            $latestLeave = LeaveRequest::query()
                ->where('user_id', $user->id)
                ->latest('request_date')
                ->first();
            $latestReportStatus = (string) (DailyReport::query()
                ->whereHas('attendance', fn ($q) => $q->where('user_id', $user->id))
                ->whereNotNull('review_status')
                ->where('review_status', '!=', '')
                ->latest('id')
                ->value('review_status') ?? '-');
            $studentDashboard = [
                'todayAttendanceStatus' => (string) ($todayAttendance?->status ?? 'awaiting'),
                'todayCheckIn' => optional($todayAttendance?->check_in_at)?->format('H:i:s') ?? '-',
                'todayCheckOut' => optional($todayAttendance?->check_out_at)?->format('H:i:s') ?? '-',
                'latestLeaveStatus' => (string) ($latestLeave?->status ?? '-'),
                'latestLeaveDate' => optional($latestLeave?->request_date)?->toDateString() ?? '-',
                'latestReportStatus' => $latestReportStatus,
            ];
        }

        if ($showAnalytics) {
            $periodLabels = $this->buildPeriodLabels($analyticsMode, $analyticsRange);
            if ($dashboardRole === 'kesiswaan') {
                $departmentOptions = $this->availableDepartments();
                if ($selectedDepartment !== '' && ! in_array($selectedDepartment, $departmentOptions, true)) {
                    $selectedDepartment = '';
                }
                $classOptions = $selectedDepartment === '' ? [] : $this->availableClasses($selectedDepartment);
                if ($selectedClass !== '' && ! in_array($selectedClass, $classOptions, true)) {
                    $selectedClass = '';
                }
                $stats = $selectedDepartment === ''
                    ? []
                    : $this->buildStats($periodLabels, $selectedDepartment, $selectedClass === '' ? null : $selectedClass);
            } elseif ($dashboardRole === 'instruktur') {
                $selectedDepartment = trim((string) ($user->department_name ?? ''));
                $selectedClass = '';
                $stats = $selectedDepartment === ''
                    ? []
                    : $this->buildStats($periodLabels, $selectedDepartment, null);
            } else {
                $stats = $this->buildStats($periodLabels);
            }
            $labels = array_keys($periodLabels);
        }

        $pembimbingSummary = null;
        $instrukturSummary = null;
        $kajurSummary = null;
        $waliSummary = null;
        if ($dashboardRole === 'pembimbing_pkl') {
            $pembimbingSummary = $this->buildPembimbingSummary($user);
            $kpiCards = [];
        } elseif ($dashboardRole === 'instruktur') {
            $instrukturSummary = $this->buildInstrukturSummary($user);
            $kpiCards = [];
        } elseif ($dashboardRole === 'kajur') {
            $kajurSummary = $this->buildKajurSummary($user);
        } elseif ($dashboardRole === 'kesiswaan') {
            $kpiCards = $selectedDepartment === ''
                ? []
                : $this->buildKpiCards($dashboardRole, $user, $selectedDepartment, $selectedClass === '' ? null : $selectedClass);
        } elseif ($dashboardRole === 'wali_kelas') {
            $waliSummary = $this->buildWaliSummary($user);
            $kpiCards = [];
        }

        return view('dashboard', [
            'title' => 'Dasbor',
            'currentRole' => $dashboardRole,
            'showAnalytics' => $showAnalytics,
            'analyticsMode' => $analyticsMode,
            'analyticsRange' => $analyticsRange,
            'chartType' => $chartType,
            'labels' => $labels,
            'hadirData' => array_values(array_column($stats, 'hadir')),
            'izinData' => array_values(array_column($stats, 'izin')),
            'sakitData' => array_values(array_column($stats, 'sakit')),
            'alphaData' => array_values(array_column($stats, 'alpha')),
            'pendingData' => array_values(array_column($stats, 'pending')),
            'kpiCards' => $kpiCards,
            'studentLocation' => $studentLocation,
            'pembimbingSummary' => $pembimbingSummary,
            'instrukturSummary' => $instrukturSummary,
            'kajurSummary' => $kajurSummary,
            'waliSummary' => $waliSummary,
            'selectedDepartment' => $selectedDepartment,
            'selectedClass' => $selectedClass,
            'departmentOptions' => $departmentOptions,
            'classOptions' => $classOptions,
            'studentDashboard' => $studentDashboard,
        ]);
    }

    public function legacyRedirect(): RedirectResponse
    {
        return redirect()->route('dashboard');
    }

    private function normalizeDashboardRole(string $role): string
    {
        return match ($role) {
            'owner' => 'kepsek',
            'operator' => 'admin_sekolah',
            default => $role,
        };
    }

    private function buildPeriodLabels(string $mode, string $range): array
    {
        $labels = [];
        $now = Carbon::now();

        if ($mode === 'daily') {
            $date = $range === 'yesterday' ? $now->copy()->subDay() : $now;
            $label = $range === 'yesterday' ? 'Kemarin' : 'Hari Ini';
            $labels[$label] = [
                'start' => $date->toDateString(),
                'end' => $date->toDateString(),
            ];

            return $labels;
        }

        $start = $range === 'last_month'
            ? $now->copy()->subMonthNoOverflow()->startOfMonth()
            : $now->copy()->startOfMonth();

        $end = $start->copy()->endOfMonth();

        while ($start->lte($end)) {
            $labels[$start->format('d M')] = [
                'start' => $start->toDateString(),
                'end' => $start->toDateString(),
            ];

            $start->addDay();
        }

        return $labels;
    }

    private function buildStats(array $labels, ?string $departmentName = null, ?string $className = null): array
    {
        $stats = [];

        foreach ($labels as $label => $range) {
            $attendanceQuery = Attendance::query()->whereBetween('attendance_date', [$range['start'], $range['end']]);
            $leaveQuery = LeaveRequest::query()->whereBetween('request_date', [$range['start'], $range['end']]);

            if ($departmentName !== null) {
                $attendanceQuery->whereHas('user', fn ($query) => $query->where('department_name', $departmentName));
                $leaveQuery->whereHas('user', fn ($query) => $query->where('department_name', $departmentName));
            }
            if ($className !== null) {
                $attendanceQuery->whereHas('user', fn ($query) => $query->where('class_name', $className));
                $leaveQuery->whereHas('user', fn ($query) => $query->where('class_name', $className));
            }

            $stats[$label] = [
                'hadir' => (clone $attendanceQuery)->where('status', 'hadir')->count(),
                'izin' => (clone $leaveQuery)->whereIn('status', ['approved', 'izin_approved'])->where('type', 'izin')->count(),
                'sakit' => (clone $leaveQuery)->whereIn('status', ['approved', 'sakit_approved'])->where('type', 'sakit')->count(),
                'alpha' => (clone $attendanceQuery)->where(function ($query): void {
                    $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
                })->count() + (clone $leaveQuery)->where(function ($query): void {
                    $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::LEAVE_REJECTS);
                })->count(),
                'pending' => (clone $attendanceQuery)->whereIn('status', WorkflowState::ATTENDANCE_PENDING)->count()
                    + (clone $leaveQuery)->whereIn('status', WorkflowState::LEAVE_PENDING)->count(),
            ];
        }

        return $stats;
    }

    private function buildKpiCards(string $role, ?User $actor = null, ?string $departmentName = null, ?string $className = null): array
    {
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();
        $attendanceQuery = Attendance::query();
        $dailyReportQuery = DailyReport::query();
        $exceptionQuery = AttendanceException::query();
        $studentScopeQuery = User::query()->where('role', 'siswa');

        if ($role === 'wali_kelas' && $actor) {
            $className = trim((string) ($actor->class_name ?? ''));
            if ($className === '') {
                $attendanceQuery->whereRaw('1 = 0');
                $dailyReportQuery->whereRaw('1 = 0');
                $exceptionQuery->whereRaw('1 = 0');
                $studentScopeQuery->whereRaw('1 = 0');
            } else {
                $attendanceQuery->whereHas('user', fn ($query) => $query->where('class_name', $className));
                $dailyReportQuery->whereHas('attendance.user', fn ($query) => $query->where('class_name', $className));
                $exceptionQuery->whereHas('user', fn ($query) => $query->where('class_name', $className));
                $studentScopeQuery->where('class_name', $className);
            }
        }

        if ($role === 'kesiswaan' && $departmentName !== null) {
            $attendanceQuery->whereHas('user', fn ($query) => $query->where('department_name', $departmentName));
            $dailyReportQuery->whereHas('attendance.user', fn ($query) => $query->where('department_name', $departmentName));
            $exceptionQuery->whereHas('user', fn ($query) => $query->where('department_name', $departmentName));
            $studentScopeQuery->where('department_name', $departmentName);
        }
        if ($role === 'kesiswaan' && $className !== null) {
            $attendanceQuery->whereHas('user', fn ($query) => $query->where('class_name', $className));
            $dailyReportQuery->whereHas('attendance.user', fn ($query) => $query->where('class_name', $className));
            $exceptionQuery->whereHas('user', fn ($query) => $query->where('class_name', $className));
            $studentScopeQuery->where('class_name', $className);
        }

        if (in_array($role, ['kajur', 'instruktur'], true) && $actor && filled($actor->department_name)) {
            $attendanceQuery->whereHas('user', fn ($query) => $query->where('department_name', $actor->department_name));
            $dailyReportQuery->whereHas('attendance.user', fn ($query) => $query->where('department_name', $actor->department_name));
            $exceptionQuery->whereHas('user', fn ($query) => $query->where('department_name', $actor->department_name));
            $studentScopeQuery->where('department_name', $actor->department_name);
        }

        if ($role === 'pembimbing_pkl' && $actor) {
            if ($actor->is_school_mentor_all_students && filled($actor->department_name)) {
                $attendanceQuery->whereHas('user', fn ($query) => $query->where('department_name', $actor->department_name));
                $dailyReportQuery->whereHas('attendance.user', fn ($query) => $query->where('department_name', $actor->department_name));
                $exceptionQuery->whereHas('user', fn ($query) => $query->where('department_name', $actor->department_name));
                $studentScopeQuery->where('department_name', $actor->department_name);
            } else {
                $attendanceQuery->whereHas('user', fn ($query) => $query->where('pembimbing_user_id', $actor->id));
                $dailyReportQuery->whereHas('attendance.user', fn ($query) => $query->where('pembimbing_user_id', $actor->id));
                $exceptionQuery->whereHas('user', fn ($query) => $query->where('pembimbing_user_id', $actor->id));
                $studentScopeQuery->where('pembimbing_user_id', $actor->id);
            }
        }

        $base = [
            ['label' => 'Hadir Hari Ini', 'value' => (clone $attendanceQuery)->whereDate('attendance_date', $today)->where('status', 'hadir')->count()],
            ['label' => 'Menunggu Validasi', 'value' => (clone $attendanceQuery)->whereIn('status', WorkflowState::ATTENDANCE_PENDING)->count()],
            ['label' => 'Menunggu Tinjau Laporan', 'value' => (clone $dailyReportQuery)->whereIn('review_status', WorkflowState::REPORT_PENDING)->count()],
            ['label' => 'Pengecualian Hari Ini', 'value' => (clone $exceptionQuery)->whereDate('event_date', $today)->count()],
            ['label' => 'SLA Terlewati (Menunggu)', 'value' => (clone $attendanceQuery)->whereIn('status', WorkflowState::ATTENDANCE_PENDING)->whereNotNull('validation_sla_due_at')->where('validation_sla_due_at', '<', now())->count()],
        ];

        if ($role !== 'siswa') {
            $studentTotalLabel = $role === 'kajur' ? 'Total Siswa Jurusan' : 'Total Siswa Aktif';
            array_unshift($base, [
                'label' => $studentTotalLabel,
                'value' => (clone $studentScopeQuery)->count(),
            ]);
        }

        if (in_array($role, ['superadmin', 'kepsek'], true)) {
            $base[] = ['label' => 'Alpha Hari Ini', 'value' => Attendance::query()->whereDate('attendance_date', $today)->where(function ($query): void {
                $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
            })->count()];
        }

        if (in_array($role, ['kajur', 'kesiswaan', 'kepsek', 'superadmin'], true)) {
            $weeklyQuery = WeeklyValidation::query()
                ->whereDate('week_start', $weekStart)
                ->whereDate('week_end', $weekEnd);

            if ($role === 'kajur' && $actor) {
                $dept = trim((string) ($actor->department_name ?? ''));
                if ($dept === '') {
                    $weeklyQuery->whereRaw('1 = 0');
                } else {
                    $weeklyQuery->where('department_name', $dept);
                }
            }

            if ($role === 'kesiswaan') {
                if ($departmentName === null || $departmentName === '') {
                    $weeklyQuery->whereRaw('1 = 0');
                } else {
                    $weeklyQuery->where('department_name', $departmentName);
                    if ($className !== null && $className !== '') {
                        $weeklyQuery->where('class_name', $className);
                    }
                }
            }

            $base[] = ['label' => 'Validasi Mingguan Disetujui', 'value' => (clone $weeklyQuery)->where('status', 'approved')->count()];
        }

        return $base;
    }

    /**
     * @return array<int, string>
     */
    private function availableDepartments(): array
    {
        return User::query()
            ->where('role', 'siswa')
            ->whereNotNull('department_name')
            ->where('department_name', '!=', '')
            ->orderBy('department_name')
            ->distinct()
            ->pluck('department_name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function availableClasses(string $departmentName): array
    {
        return User::query()
            ->where('role', 'siswa')
            ->where('department_name', $departmentName)
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->orderBy('class_name')
            ->distinct()
            ->pluck('class_name')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPembimbingSummary(User $user): array
    {
        $today = now()->toDateString();
        $locationIds = PklLocation::query()
            ->where('pembimbing_user_id', $user->id)
            ->pluck('id');

        $siswaBinaanCount = User::query()
            ->where('role', 'siswa')
            ->whereIn('pkl_location_id', $locationIds)
            ->count();

        $pendingAbsensiCount = Attendance::query()
            ->whereIn('pkl_location_id', $locationIds)
            ->whereIn('status', WorkflowState::ATTENDANCE_PENDING)
            ->count();

        $pendingPengajuanCount = LeaveRequest::query()
            ->whereIn('pkl_location_id', $locationIds)
            ->whereIn('status', WorkflowState::LEAVE_PENDING)
            ->count();

        $pendingLaporanCount = DailyReport::query()
            ->where('review_status', 'pending_pembimbing')
            ->whereHas('attendance', function ($query) use ($locationIds): void {
                $query->whereIn('pkl_location_id', $locationIds);
            })
            ->count();

        $hadirHariIni = Attendance::query()
            ->whereIn('pkl_location_id', $locationIds)
            ->whereDate('attendance_date', $today)
            ->where('status', 'hadir')
            ->count();

        $alphaHariIni = Attendance::query()
            ->whereIn('pkl_location_id', $locationIds)
            ->whereDate('attendance_date', $today)
            ->where(function ($query): void {
                $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
            })
            ->count();

        $overdueCount = Attendance::query()
            ->whereIn('pkl_location_id', $locationIds)
            ->whereIn('status', WorkflowState::ATTENDANCE_PENDING)
            ->whereNotNull('validation_sla_due_at')
            ->where('validation_sla_due_at', '<', now())
            ->count()
            + LeaveRequest::query()
                ->whereIn('pkl_location_id', $locationIds)
                ->whereIn('status', WorkflowState::LEAVE_PENDING)
                ->whereNotNull('validation_sla_due_at')
                ->where('validation_sla_due_at', '<', now())
                ->count()
            + DailyReport::query()
                ->where('review_status', 'pending_pembimbing')
                ->whereNotNull('review_sla_due_at')
                ->where('review_sla_due_at', '<', now())
                ->whereHas('attendance', function ($query) use ($locationIds): void {
                    $query->whereIn('pkl_location_id', $locationIds);
                })
                ->count();

        $pendingAbsensi = Attendance::query()
            ->with('user')
            ->whereIn('pkl_location_id', $locationIds)
            ->whereIn('status', WorkflowState::ATTENDANCE_PENDING)
            ->latest('attendance_date')
            ->limit(5)
            ->get();

        $pendingPengajuan = LeaveRequest::query()
            ->with('user')
            ->whereIn('pkl_location_id', $locationIds)
            ->whereIn('status', WorkflowState::LEAVE_PENDING)
            ->latest('request_date')
            ->limit(5)
            ->get();

        $pendingLaporan = DailyReport::query()
            ->with('attendance.user')
            ->where('review_status', 'pending_pembimbing')
            ->whereHas('attendance', function ($query) use ($locationIds): void {
                $query->whereIn('pkl_location_id', $locationIds);
            })
            ->latest()
            ->limit(5)
            ->get();

        return [
            'cards' => [
                ['label' => 'Siswa Binaan Aktif', 'value' => $siswaBinaanCount],
                ['label' => 'Pending Absensi', 'value' => $pendingAbsensiCount],
                ['label' => 'Pending Pengajuan', 'value' => $pendingPengajuanCount],
                ['label' => 'Pending Laporan', 'value' => $pendingLaporanCount],
                ['label' => 'SLA Terlewati', 'value' => $overdueCount],
            ],
            'chart' => [
                'labels' => ['Hadir Hari Ini', 'Pending Absensi', 'Pending Pengajuan', 'Pending Laporan', 'Alpha Hari Ini'],
                'values' => [$hadirHariIni, $pendingAbsensiCount, $pendingPengajuanCount, $pendingLaporanCount, $alphaHariIni],
            ],
            'pendingAbsensi' => $pendingAbsensi,
            'pendingPengajuan' => $pendingPengajuan,
            'pendingLaporan' => $pendingLaporan,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWaliSummary(User $user): array
    {
        $className = trim((string) ($user->class_name ?? ''));
        if ($className === '') {
            return [
                'className' => null,
                'cards' => [],
                'students' => collect(),
                'attendanceMap' => [],
                'leaveMap' => [],
                'monitoringToday' => [],
                'analytics' => [
                    'alphaTop' => [],
                    'pendingTop' => [],
                    'violationTop' => [],
                    'bestTop' => [],
                    'worstTop' => [],
                    'alerts' => [
                        'alpha_over_2' => [],
                        'pending_over_2' => [],
                        'missing_report_over_2' => [],
                        'outside_radius_over_2' => [],
                    ],
                ],
            ];
        }

        $students = User::query()
            ->where('role', 'siswa')
            ->where('class_name', $className)
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'department_name']);

        $today = now()->toDateString();
        $isOptionalDay = in_array(now()->dayOfWeekIso, [6, 7], true);
        $studentIds = $students->pluck('id');

        $attendanceMap = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereIn('user_id', $studentIds)
            ->get(['user_id', 'status'])
            ->pluck('status', 'user_id')
            ->toArray();

        $leaveMap = LeaveRequest::query()
            ->whereDate('request_date', $today)
            ->whereIn('user_id', $studentIds)
            ->get(['user_id', 'status', 'type'])
            ->keyBy('user_id')
            ->toArray();

        $hadirToday = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereIn('user_id', $studentIds)
            ->where('status', 'hadir')
            ->count();

        $pendingToday = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereIn('user_id', $studentIds)
            ->whereIn('status', WorkflowState::ATTENDANCE_PENDING)
            ->count();

        $leaveToday = LeaveRequest::query()
            ->whereDate('request_date', $today)
            ->whereIn('user_id', $studentIds)
            ->count();

        $coveredTodayIds = Attendance::query()
            ->whereDate('attendance_date', $today)
            ->whereIn('user_id', $studentIds)
            ->pluck('user_id')
            ->merge(
                LeaveRequest::query()
                    ->whereDate('request_date', $today)
                    ->whereIn('user_id', $studentIds)
                    ->pluck('user_id')
            )
            ->unique();

        $belumCheckin = $isOptionalDay ? 0 : max($students->count() - $coveredTodayIds->count(), 0);

        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $weeklyAttendances = Attendance::query()
            ->with('report')
            ->whereIn('user_id', $studentIds)
            ->whereBetween('attendance_date', [$weekStart, $weekEnd])
            ->get(['id', 'user_id', 'attendance_date', 'status', 'check_in_at', 'check_out_at']);

        $weeklyExceptions = AttendanceException::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('event_date', [$weekStart, $weekEnd])
            ->get(['user_id', 'exception_type']);

        $todayAttendanceRows = Attendance::query()
            ->with('report')
            ->whereDate('attendance_date', $today)
            ->whereIn('user_id', $studentIds)
            ->get(['id', 'user_id', 'status', 'check_in_at', 'check_out_at'])
            ->keyBy('user_id');

        $monitoringToday = [];
        $metrics = [];
        foreach ($students as $student) {
            $uid = (int) $student->id;
            $attendanceToday = $todayAttendanceRows->get($uid);
            $hasCheckin = ! empty($attendanceToday?->check_in_at);
            $hasCheckout = ! empty($attendanceToday?->check_out_at);
            $actualToday = trim((string) ($attendanceToday?->report?->actual_work ?? ''));
            $monitoringToday[] = [
                'id' => $uid,
                'name' => (string) $student->name,
                'nis' => (string) ($student->nis ?? '-'),
                'department_name' => (string) ($student->department_name ?? '-'),
                'has_checkin' => $hasCheckin,
                'has_checkout' => $hasCheckout,
                'has_daily_report' => $actualToday !== '',
            ];

            $metrics[$uid] = [
                'id' => $uid,
                'name' => (string) $student->name,
                'nis' => (string) ($student->nis ?? '-'),
                'class_name' => $className,
                'hadir' => 0,
                'alpha' => 0,
                'pending' => 0,
                'pending_days' => [],
                'missing_report_days' => [],
                'violation_c' => 0,
                'violation_e' => 0,
                'outside_radius' => 0,
            ];
        }

        foreach ($weeklyAttendances as $attendance) {
            $uid = (int) $attendance->user_id;
            if (! isset($metrics[$uid])) {
                continue;
            }
            $status = (string) ($attendance->status ?? '');
            $day = (string) optional($attendance->attendance_date)->toDateString();
            if ($status === 'hadir') {
                $metrics[$uid]['hadir']++;
            }
            if ($status === 'alpha' || in_array($status, WorkflowState::ATTENDANCE_REJECTS, true)) {
                $metrics[$uid]['alpha']++;
            }
            if (in_array($status, WorkflowState::ATTENDANCE_PENDING, true)) {
                $metrics[$uid]['pending']++;
                if ($day !== '') {
                    $metrics[$uid]['pending_days'][$day] = true;
                }
            }

            $actualWork = trim((string) ($attendance->report?->actual_work ?? ''));
            if ($attendance->check_out_at && $actualWork === '' && $day !== '') {
                $metrics[$uid]['missing_report_days'][$day] = true;
            }
        }

        foreach ($weeklyExceptions as $exception) {
            $uid = (int) $exception->user_id;
            if (! isset($metrics[$uid])) {
                continue;
            }
            $type = strtolower((string) $exception->exception_type);
            if (str_contains($type, 'meninggalkan') || str_contains($type, 'leave')) {
                $metrics[$uid]['violation_c']++;
            }
            if (str_contains($type, 'late') || str_contains($type, 'telat') || str_contains($type, 'terlambat')) {
                $metrics[$uid]['violation_e']++;
            }
            if (str_contains($type, 'outside') || str_contains($type, 'radius') || str_contains($type, 'luar radius')) {
                $metrics[$uid]['outside_radius']++;
            }
        }

        $rows = collect(array_values($metrics))->map(function (array $row): array {
            $row['pending_days_count'] = count($row['pending_days']);
            $row['missing_report_days_count'] = count($row['missing_report_days']);
            $row['violation_total'] = (int) $row['violation_c'] + (int) $row['violation_e'];
            $row['best_score'] = ((int) $row['hadir'] * 10) - ((int) $row['alpha'] * 8) - ((int) $row['pending_days_count'] * 4) - ((int) $row['violation_total'] * 5);
            $row['risk_score'] = ((int) $row['alpha'] * 10) + ((int) $row['pending_days_count'] * 6) + ((int) $row['violation_total'] * 7) + ((int) $row['outside_radius'] * 4);
            return $row;
        });

        return [
            'className' => $className,
            'cards' => [
                ['label' => 'Total Siswa Kelas', 'value' => $students->count()],
                ['label' => 'Hadir Hari Ini', 'value' => $hadirToday],
                ['label' => 'Menunggu Validasi', 'value' => $pendingToday],
                ['label' => 'Pengajuan Hari Ini', 'value' => $leaveToday],
                ['label' => 'Belum Check-in', 'value' => $belumCheckin],
            ],
            'students' => $students,
            'attendanceMap' => $attendanceMap,
            'leaveMap' => $leaveMap,
            'monitoringToday' => $monitoringToday,
            'analytics' => [
                'alphaTop' => $rows->where('alpha', '>', 0)->sortByDesc('alpha')->take(10)->values()->all(),
                'pendingTop' => $rows->where('pending_days_count', '>', 0)->sortByDesc('pending_days_count')->take(10)->values()->all(),
                'violationTop' => $rows->where('violation_total', '>', 0)->sortByDesc('violation_total')->take(10)->values()->all(),
                'bestTop' => $rows->sortByDesc('best_score')->take(10)->values()->all(),
                'worstTop' => $rows->sortByDesc('risk_score')->take(10)->values()->all(),
                'alerts' => [
                    'alpha_over_2' => $rows->where('alpha', '>', 2)->values()->all(),
                    'pending_over_2' => $rows->where('pending_days_count', '>', 2)->values()->all(),
                    'missing_report_over_2' => $rows->where('missing_report_days_count', '>', 2)->values()->all(),
                    'outside_radius_over_2' => $rows->where('outside_radius', '>', 2)->values()->all(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildInstrukturSummary(User $user): array
    {
        $departmentName = trim((string) ($user->department_name ?? ''));
        if ($departmentName === '') {
            return [
                'departmentName' => null,
                'cards' => [],
                'pendingAttendance' => collect(),
                'weeklyNotes' => collect(),
            ];
        }

        $today = now()->toDateString();
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $studentIds = User::query()
            ->where('role', 'siswa')
            ->where('department_name', $departmentName)
            ->pluck('id');

        $pendingAttendance = Attendance::query()
            ->with('user')
            ->whereIn('user_id', $studentIds)
            ->whereIn('status', WorkflowState::ATTENDANCE_PENDING)
            ->latest('attendance_date')
            ->limit(5)
            ->get();

        $weeklyNotes = WeeklyValidation::query()
            ->where('department_name', $departmentName)
            ->whereBetween('week_start', [$weekStart, $weekEnd])
            ->orderByDesc('week_start')
            ->limit(5)
            ->get();

        return [
            'departmentName' => $departmentName,
            'cards' => [
                [
                    'label' => 'Total Siswa Jurusan',
                    'value' => (clone $studentIds)->count(),
                ],
                [
                    'label' => 'Hadir Hari Ini',
                    'value' => Attendance::query()
                        ->whereIn('user_id', $studentIds)
                        ->whereDate('attendance_date', $today)
                        ->where('status', 'hadir')
                        ->count(),
                ],
                [
                    'label' => 'Pending Check-in/Check-out',
                    'value' => Attendance::query()
                        ->whereIn('user_id', $studentIds)
                        ->whereIn('status', WorkflowState::ATTENDANCE_PENDING)
                        ->count(),
                ],
                [
                    'label' => 'Catatan Mingguan Minggu Ini',
                    'value' => WeeklyValidation::query()
                        ->where('department_name', $departmentName)
                        ->whereBetween('week_start', [$weekStart, $weekEnd])
                        ->whereNotNull('instruktur_note')
                        ->count(),
                ],
            ],
            'pendingAttendance' => $pendingAttendance,
            'weeklyNotes' => $weeklyNotes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildKajurSummary(User $user): array
    {
        $departmentName = trim((string) ($user->department_name ?? ''));
        if ($departmentName === '') {
            return [
                'departmentName' => null,
                'cards' => [],
                'recentWeekly' => collect(),
                'alphaTodayRows' => collect(),
            ];
        }

        $today = now()->toDateString();
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $studentIds = User::query()
            ->where('role', 'siswa')
            ->where('department_name', $departmentName)
            ->pluck('id');

        $recentWeekly = WeeklyValidation::query()
            ->where('department_name', $departmentName)
            ->orderByDesc('week_start')
            ->limit(5)
            ->get();

        $alphaTodayRows = Attendance::query()
            ->with('user:id,name,nis')
            ->whereIn('user_id', $studentIds)
            ->whereDate('attendance_date', $today)
            ->where(function ($query): void {
                $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
            })
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return [
            'departmentName' => $departmentName,
            'cards' => [
                [
                    'label' => 'Total Siswa Jurusan',
                    'value' => (clone $studentIds)->count(),
                ],
                [
                    'label' => 'Hadir Hari Ini',
                    'value' => Attendance::query()
                        ->whereIn('user_id', $studentIds)
                        ->whereDate('attendance_date', $today)
                        ->where('status', 'hadir')
                        ->count(),
                ],
                [
                    'label' => 'Alpha Hari Ini',
                    'value' => Attendance::query()
                        ->whereIn('user_id', $studentIds)
                        ->whereDate('attendance_date', $today)
                        ->where(function ($query): void {
                            $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
                        })
                        ->count(),
                ],
                [
                    'label' => 'Validasi Mingguan Menunggu',
                    'value' => WeeklyValidation::query()
                        ->where('department_name', $departmentName)
                        ->whereDate('week_start', $weekStart)
                        ->whereDate('week_end', $weekEnd)
                        ->where('status', 'pending')
                        ->count(),
                ],
                [
                    'label' => 'Validasi Mingguan Disetujui',
                    'value' => WeeklyValidation::query()
                        ->where('department_name', $departmentName)
                        ->whereDate('week_start', $weekStart)
                        ->whereDate('week_end', $weekEnd)
                        ->where('status', 'approved')
                        ->count(),
                ],
            ],
            'recentWeekly' => $recentWeekly,
            'alphaTodayRows' => $alphaTodayRows,
        ];
    }
}
