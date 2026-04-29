<?php

namespace App\Http\Controllers;

use App\Models\AttendanceException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceExceptionController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = (int) $request->integer('per_page', 30);
        $allowedPerPage = [10, 20, 30, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 30;
        }

        $query = AttendanceException::query()->with(['user', 'attendance', 'resolver'])->latest('event_date');

        if ($request->filled('type')) {
            $query->where('exception_type', $request->string('type')->toString());
        }

        if ($request->filled('severity')) {
            $query->where('severity', $request->string('severity')->toString());
        }

        if ($request->filled('date_from')) {
            $query->whereDate('event_date', '>=', $request->string('date_from')->toString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('event_date', '<=', $request->string('date_to')->toString());
        }

        return view('exceptions.index', [
            'title' => 'Exception Monitoring',
            'items' => $query->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
            'types' => AttendanceException::query()->select('exception_type')->distinct()->orderBy('exception_type')->pluck('exception_type')->all(),
        ]);
    }
}
