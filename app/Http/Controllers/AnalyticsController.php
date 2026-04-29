<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(): View
    {
        $weekly = Attendance::selectRaw('DATE(attendance_date) as label, COUNT(*) as total')
            ->whereDate('attendance_date', '>=', now()->subDays(6)->toDateString())
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $monthly = Attendance::selectRaw("DATE_FORMAT(attendance_date, '%Y-%m') as label, COUNT(*) as total")
            ->whereDate('attendance_date', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        $yearly = Attendance::selectRaw('YEAR(attendance_date) as label, COUNT(*) as total')
            ->whereDate('attendance_date', '>=', now()->subYears(4)->startOfYear()->toDateString())
            ->groupBy('label')
            ->orderBy('label')
            ->get();

        return view('analytics.index', [
            'weeklyLabels' => $weekly->pluck('label')->values(),
            'weeklyTotals' => $weekly->pluck('total')->values(),
            'monthlyLabels' => $monthly->pluck('label')->values(),
            'monthlyTotals' => $monthly->pluck('total')->values(),
            'yearlyLabels' => $yearly->pluck('label')->values(),
            'yearlyTotals' => $yearly->pluck('total')->values(),
        ]);
    }
}
