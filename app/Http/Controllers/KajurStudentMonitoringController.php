<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\MentorRoleScope;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KajurStudentMonitoringController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $departmentName = $this->resolveDepartmentScope($request);
        $isAdminScopeRole = in_array((string) ($user->role ?? ''), ['admin_sekolah', 'superadmin'], true);
        abort_if($departmentName === '' && ! $isAdminScopeRole, 422, 'Pilih jurusan terlebih dahulu.');

        $q = trim((string) $request->string('q')->toString());
        $className = trim((string) $request->string('class_name')->toString());
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
        $students = User::query()
            ->where('role', 'siswa')
            ->when($departmentName !== '', fn ($query) => $query->where('department_name', $departmentName))
            ->when($departmentName === '', fn ($query) => $query->whereRaw('1 = 0'))
            ->when($className !== '', fn ($query) => $query->where('class_name', $className))
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($sub) use ($q): void {
                    $sub->where('name', 'like', '%'.$q.'%')
                        ->orWhere('nis', 'like', '%'.$q.'%')
                        ->orWhere('class_name', 'like', '%'.$q.'%');
                });
            })
            ->orderBy('class_name')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $schoolMentors = $departmentName === ''
            ? new EloquentCollection()
            : User::query()
                ->whereIn('role', ['pembimbing_pkl', 'pembimbing'])
                ->where('department_name', $departmentName)
                ->orderBy('name')
                ->get(['id', 'name', 'is_school_mentor_all_students']);

        $instructors = $departmentName === ''
            ? new EloquentCollection()
            : User::query()
                ->where('role', 'instruktur')
                ->where('department_name', $departmentName)
                ->orderBy('name')
                ->get(['id', 'name']);

        return view('kajur.students.index', [
            'title' => 'Monitoring Absensi Siswa Jurusan',
            'students' => $students,
            'schoolMentors' => $schoolMentors,
            'instructors' => $instructors,
            'assignmentMap' => $this->buildAssignmentMap($students->getCollection()->pluck('id')->all()),
            'departmentName' => $departmentName,
            'departmentOptions' => User::query()
                ->where('role', 'siswa')
                ->whereNotNull('department_name')
                ->where('department_name', '!=', '')
                ->orderBy('department_name')
                ->distinct()
                ->pluck('department_name')
                ->values(),
            'classOptions' => $departmentName === ''
                ? collect()
                : User::query()
                    ->where('role', 'siswa')
                    ->where('department_name', $departmentName)
                    ->whereNotNull('class_name')
                    ->where('class_name', '!=', '')
                    ->orderBy('class_name')
                    ->distinct()
                    ->pluck('class_name')
                    ->values(),
            'filters' => ['q' => $q, 'class_name' => $className, 'per_page' => $perPage],
            'perPageOptions' => $allowedPerPage,
        ]);
    }

    public function show(Request $request, User $student): View
    {
        $user = $request->user();
        $departmentName = $this->resolveDepartmentScope($request);
        abort_if($departmentName === '', 403, 'Jurusan kajur belum diatur.');
        abort_if($student->role !== 'siswa' || (string) ($student->department_name ?? '') !== $departmentName, 403, 'Siswa di luar scope jurusan.');

        $attendances = Attendance::query()
            ->where('user_id', $student->id)
            ->latest('attendance_date')
            ->paginate(31)
            ->withQueryString();

        $addressRows = Attendance::query()
            ->where('user_id', $student->id)
            ->orderByDesc('attendance_date')
            ->orderByDesc('id')
            ->get([
                'id',
                'check_in_location_address',
                'check_in_location_label',
                'check_in_latitude',
                'check_in_longitude',
            ]);

        $consistencyById = [];
        $summary = [
            'same' => 0,
            'different' => 0,
            'no_compare' => 0,
            'no_address' => 0,
        ];

        $totalRows = $addressRows->count();
        foreach ($addressRows as $index => $row) {
            $currentKey = $this->addressKey($row);
            $prevRow = $index < ($totalRows - 1) ? $addressRows[$index + 1] : null;
            $prevKey = $prevRow ? $this->addressKey($prevRow) : null;

            if ($currentKey === null) {
                $status = 'no_address';
            } elseif ($prevKey === null) {
                $status = 'no_compare';
            } else {
                $status = $currentKey === $prevKey ? 'same' : 'different';
            }

            $consistencyById[(int) $row->id] = $status;
            $summary[$status]++;
        }

        $attendances->getCollection()->transform(function (Attendance $attendance) use ($consistencyById): Attendance {
            $attendance->setAttribute('address_consistency', $consistencyById[(int) $attendance->id] ?? 'no_compare');
            return $attendance;
        });

        return view('kajur.students.show', [
            'title' => 'Detail Absensi Siswa',
            'student' => $student,
            'attendances' => $attendances,
            'departmentName' => $departmentName,
            'addressConsistencySummary' => $summary,
        ]);
    }

    public function assignMentor(Request $request, User $student): RedirectResponse|JsonResponse
    {
        $actor = $request->user();
        $departmentName = $this->resolveDepartmentScope($request);
        abort_if($departmentName === '', 403, 'Jurusan kajur belum diatur.');
        abort_if($student->role !== 'siswa' || (string) ($student->department_name ?? '') !== $departmentName, 403, 'Siswa di luar scope jurusan.');

        $data = $request->validate([
            'mentor_role' => ['required', Rule::in(['pembimbing_pkl', 'instruktur'])],
            'mentor_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
        ]);

        $mentorRole = (string) $data['mentor_role'];
        $mentorId = (int) ($data['mentor_user_id'] ?? 0);

        StudentMentorAssignment::query()
            ->where('student_user_id', (int) $student->id)
            ->where('mentor_role', $mentorRole)
            ->delete();

        if ($mentorId > 0) {
            $mentor = User::query()->findOrFail($mentorId);
            abort_if(! $this->mentorRoleMatches((string) $mentor->role, $mentorRole) || (string) ($mentor->department_name ?? '') !== $departmentName, 422, 'Mentor tidak valid untuk jurusan ini.');

            StudentMentorAssignment::query()->create([
                'student_user_id' => (int) $student->id,
                'mentor_user_id' => $mentorId,
                'mentor_role' => $mentorRole,
                'assigned_by' => (int) ($actor->id ?? 0),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Assignment mentor untuk siswa berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Assignment mentor untuk siswa berhasil diperbarui.');
    }

    public function assignMentorForDepartment(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $departmentName = $this->resolveDepartmentScope($request);
        abort_if($departmentName === '', 403, 'Jurusan kajur belum diatur.');

        $data = $request->validate([
            'mentor_role' => ['required', Rule::in(['pembimbing_pkl', 'instruktur'])],
            'mentor_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'class_name' => ['nullable', 'string', 'max:100'],
            'selected_ids' => ['nullable', 'array'],
            'selected_ids.*' => ['integer'],
            'apply_all' => ['nullable', 'boolean'],
        ]);

        $mentorRole = (string) $data['mentor_role'];
        $selectedClass = trim((string) ($data['class_name'] ?? ''));
        $applyToAll = (bool) ($data['apply_all'] ?? false);
        $selectedIds = collect($data['selected_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        $mentor = User::query()->findOrFail((int) $data['mentor_user_id']);
        abort_if(! $this->mentorRoleMatches((string) $mentor->role, $mentorRole) || (string) ($mentor->department_name ?? '') !== $departmentName, 422, 'Mentor tidak valid.');
        abort_if(! $applyToAll && $selectedIds === [], 422, 'Pilih siswa terlebih dahulu.');

        $studentIds = User::query()
            ->where('role', 'siswa')
            ->where('department_name', $departmentName)
            ->when($selectedClass !== '', fn ($query) => $query->where('class_name', $selectedClass))
            ->when(! $applyToAll, fn ($query) => $query->whereIn('id', $selectedIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        StudentMentorAssignment::query()
            ->whereIn('student_user_id', $studentIds)
            ->where('mentor_role', $mentorRole)
            ->delete();

        $insertRows = array_map(
            fn (int $studentId) => [
                'student_user_id' => $studentId,
                'mentor_user_id' => (int) $mentor->id,
                'mentor_role' => $mentorRole,
                'assigned_by' => (int) ($actor->id ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $studentIds
        );
        if ($insertRows !== []) {
            StudentMentorAssignment::query()->insert($insertRows);
        }

        MentorRoleScope::query()->updateOrCreate(
            ['mentor_user_id' => (int) $mentor->id, 'mentor_role' => $mentorRole],
            [
                'all_students_in_department' => (bool) ($data['apply_all'] ?? true),
                'updated_by' => (int) ($actor->id ?? 0),
            ]
        );

        $updated = count($studentIds);
        $scopeLabel = $applyToAll
            ? ($selectedClass !== '' ? "semua siswa kelas {$selectedClass}" : "semua siswa jurusan {$departmentName}")
            : "siswa terpilih ({$updated})";
        return back()->with('success', "Assignment mentor {$mentorRole} untuk {$scopeLabel} berhasil ({$updated} siswa).");
    }

    /**
     * @param array<int, int> $studentIds
     * @return array<int, array{pembimbing_pkl:?int,instruktur:?int}>
     */
    private function buildAssignmentMap(array $studentIds): array
    {
        if ($studentIds === []) {
            return [];
        }

        $assignments = StudentMentorAssignment::query()
            ->whereIn('student_user_id', $studentIds)
            ->whereIn('mentor_role', ['pembimbing_pkl', 'instruktur'])
            ->orderByDesc('id')
            ->get(['student_user_id', 'mentor_user_id', 'mentor_role']);

        $map = [];
        foreach ($assignments as $assignment) {
            $sid = (int) $assignment->student_user_id;
            $role = (string) $assignment->mentor_role;
            $map[$sid] ??= ['pembimbing_pkl' => null, 'instruktur' => null];
            if ($map[$sid][$role] === null) {
                $map[$sid][$role] = (int) $assignment->mentor_user_id;
            }
        }

        return $map;
    }

    private function addressKey(Attendance $attendance): ?string
    {
        $address = trim((string) ($attendance->check_in_location_address ?? ''));
        if ($address !== '') {
            return preg_replace('/\s+/', ' ', mb_strtolower($address));
        }

        $label = trim((string) ($attendance->check_in_location_label ?? ''));
        if ($label !== '') {
            return 'label:'.preg_replace('/\s+/', ' ', mb_strtolower($label));
        }

        $lat = $attendance->check_in_latitude;
        $lng = $attendance->check_in_longitude;
        if ($lat !== null && $lng !== null) {
            return 'coord:'.$lat.','.$lng;
        }

        return null;
    }

    private function resolveDepartmentScope(Request $request): string
    {
        $actor = $request->user();
        $role = (string) ($actor->role ?? '');
        if (in_array($role, ['kajur', 'instruktur', 'pembimbing_pkl'], true)) {
            return trim((string) ($actor->department_name ?? ''));
        }
        if (in_array($role, ['admin_sekolah', 'superadmin'], true)) {
            return trim((string) $request->string('jurusan')->toString());
        }
        return '';
    }

    private function mentorRoleMatches(string $userRole, string $mentorRole): bool
    {
        if ($mentorRole === 'pembimbing_pkl') {
            return in_array($userRole, ['pembimbing_pkl', 'pembimbing'], true);
        }

        return $userRole === $mentorRole;
    }
}
