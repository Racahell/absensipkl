<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Support\WorkflowState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportChartController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $period = $request->string('period', 'monthly')->toString();
        $chartType = $request->string('chart_type', 'bar')->toString();
        $selectedDepartment = $request->string('jurusan')->toString();
        $selectedClass = $request->string('kelas')->toString();
        $departmentOptions = [];
        $classOptions = [];

        if (! in_array($period, ['weekly', 'monthly', 'yearly'], true)) {
            $period = 'monthly';
        }

        if (! in_array($chartType, ['bar', 'line', 'pie'], true)) {
            $chartType = 'bar';
        }

        $labels = $this->buildLabels($period);
        $waliClassName = $user?->role === 'wali_kelas'
            ? trim((string) ($user->class_name ?? ''))
            : null;

        $departmentFilter = null;
        $classFilter = null;
        $isDepartmentScoped = in_array($user?->role, ['kesiswaan', 'kajur'], true);
        if ($isDepartmentScoped) {
            $departmentOptions = $this->availableDepartments();
            if ($user?->role === 'kajur') {
                $selectedDepartment = trim((string) ($user?->department_name ?? ''));
            } elseif ($selectedDepartment !== '' && ! in_array($selectedDepartment, $departmentOptions, true)) {
                $selectedDepartment = '';
            }
            $departmentFilter = $selectedDepartment !== '' ? $selectedDepartment : '__empty__';
            if ($departmentFilter !== '__empty__') {
                $classOptions = $this->availableClasses($selectedDepartment);
                if ($selectedClass !== '' && ! in_array($selectedClass, $classOptions, true)) {
                    $selectedClass = '';
                }
                $classFilter = $selectedClass !== '' ? $selectedClass : null;
            }
        }

        $stats = $this->buildStats($labels, $waliClassName, $departmentFilter, $classFilter);

        return view('reports.charts', [
            'title' => 'Laporan',
            'period' => $period,
            'chartType' => $chartType,
            'labels' => array_keys($labels),
            'hadirData' => array_values(array_column($stats, 'hadir')),
            'izinData' => array_values(array_column($stats, 'izin')),
            'sakitData' => array_values(array_column($stats, 'sakit')),
            'alphaData' => array_values(array_column($stats, 'alpha')),
            'pendingData' => array_values(array_column($stats, 'pending')),
            'selectedDepartment' => $selectedDepartment,
            'selectedClass' => $selectedClass,
            'departmentOptions' => $departmentOptions,
            'classOptions' => $classOptions,
            'isKesiswaan' => $user?->role === 'kesiswaan',
            'isDepartmentScoped' => $isDepartmentScoped,
            'isKajur' => $user?->role === 'kajur',
        ]);
    }

    private function buildLabels(string $period): array
    {
        $labels = [];
        $now = Carbon::now();

        if ($period === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $date = $now->copy()->subDays($i);
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

    private function buildStats(array $labels, ?string $waliClassName = null, ?string $departmentName = null, ?string $className = null): array
    {
        $stats = [];

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

            $stats[$label] = [
                'hadir' => (clone $att)->where('status', 'hadir')->count(),
                'izin' => (clone $lv)->where('status', 'izin_approved')->count(),
                'sakit' => (clone $lv)->where('status', 'sakit_approved')->count(),
                'alpha' => (clone $att)->where(function ($query): void {
                    $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
                })->count() + (clone $lv)->where(function ($query): void {
                    $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::LEAVE_REJECTS);
                })->count(),
                'pending' => (clone $att)->whereIn('status', WorkflowState::ATTENDANCE_PENDING)->count()
                    + (clone $lv)->whereIn('status', WorkflowState::LEAVE_PENDING)->count(),
            ];
        }

        return $stats;
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
}
