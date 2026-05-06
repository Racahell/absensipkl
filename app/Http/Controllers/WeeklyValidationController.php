<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceException;
use App\Models\LeaveRequest;
use App\Models\PklLocation;
use App\Models\User;
use App\Models\StudentGuidanceNote;
use App\Models\WeeklyValidation;
use App\Support\ValidationLogger;
use App\Support\WorkflowState;
use App\Support\StudentMentorScopeResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WeeklyValidationController extends Controller
{
    public function index(Request $request): View
    {
        $data = $this->buildWeeklyPageData($request);
        $data['title'] = 'Validasi Mingguan';

        return view('reports.weekly', $data);
    }

    public function recap(Request $request): View
    {
        $data = $this->buildWeeklyPageData($request);
        $data['title'] = 'Rekap Mingguan';

        return view('reports.weekly-recap', $data);
    }

    public function analysis(Request $request): View
    {
        $data = $this->buildWeeklyPageData($request);
        $data['title'] = 'Analisis Mingguan';

        return view('reports.weekly-analysis', $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWeeklyPageData(Request $request): array
    {
        $user = $request->user();
        $role = $this->normalizeRole((string) ($user->role ?? ''));

        $weekStartInput = (string) $request->string('week_start')->toString();
        $selectedDate = $weekStartInput !== ''
            ? Carbon::parse($weekStartInput)->toDateString()
            : now(config('app.timezone', 'Asia/Jakarta'))->toDateString();
        $weekStart = $this->resolveWeekStart($weekStartInput);
        $weekEnd = $weekStart->copy()->addDays(6);
        $selectedDepartment = trim((string) $request->string('jurusan')->toString());
        $selectedClass = trim((string) $request->string('kelas')->toString());
        $selectedStudent = trim((string) $request->string('siswa')->toString());
        $requireDepartmentSelection = ! in_array($role, ['kajur', 'wali_kelas', 'instruktur', 'pembimbing_pkl'], true);
        $actorClassName = trim((string) ($user->class_name ?? ''));

        $departmentOptions = $this->availableDepartments();
        $classOptions = [];
        $studentOptions = [];

        if ($role === 'pembimbing_pkl') {
            $studentOptions = $this->availableStudents(null, null, (int) $user->id);
            if ($selectedStudent !== '' && ! collect($studentOptions)->pluck('id')->contains((int) $selectedStudent)) {
                $selectedStudent = '';
            }
            $selectedDepartment = '';
            if ($actorClassName !== '') {
                $selectedClass = $actorClassName;
                $studentOptions = collect($studentOptions)
                    ->filter(fn (array $student): bool => (string) ($student['class_name'] ?? '') === $actorClassName)
                    ->values()
                    ->all();
            } else {
                $selectedClass = '';
            }
        }

        if ($role === 'kajur') {
            $selectedDepartment = trim((string) ($user->department_name ?? ''));
            $selectedClass = '';
        }
        if ($role === 'instruktur') {
            $selectedDepartment = trim((string) ($user->department_name ?? ''));
            if ($actorClassName !== '') {
                $selectedClass = $actorClassName;
            } else {
                $selectedClass = '';
            }
        }
        if ($role === 'wali_kelas') {
            $selectedClass = trim((string) ($user->class_name ?? ''));
            if ($selectedClass !== '') {
                $classOwner = User::query()
                    ->where('role', 'siswa')
                    ->where('class_name', $selectedClass)
                    ->value('department_name');
                if ($classOwner) {
                    $selectedDepartment = (string) $classOwner;
                }
            }
        }
        if ($role !== 'siswa' && $role !== 'wali_kelas' && $actorClassName !== '') {
            $selectedClass = $actorClassName;
            if ($selectedDepartment === '') {
                $classOwner = User::query()
                    ->where('role', 'siswa')
                    ->where('class_name', $selectedClass)
                    ->value('department_name');
                if ($classOwner) {
                    $selectedDepartment = (string) $classOwner;
                }
            }
        }
        if ($selectedDepartment === '') {
            $classOptions = $requireDepartmentSelection ? [] : $this->availableClasses(null);
        } else {
            $classOptions = $this->availableClasses($selectedDepartment);
        }
        if ($selectedClass !== '' && ! in_array($selectedClass, $classOptions, true)) {
            $selectedClass = '';
        }

        if ($role !== 'pembimbing_pkl') {
            $studentOptions = ($requireDepartmentSelection && $selectedDepartment === '')
                ? []
                : $this->availableStudents($selectedDepartment === '' ? null : $selectedDepartment, $selectedClass === '' ? null : $selectedClass);
            if ($selectedStudent !== '' && ! collect($studentOptions)->pluck('id')->contains((int) $selectedStudent)) {
                $selectedStudent = '';
            }
        }

        $scopeDepartment = ($requireDepartmentSelection && $selectedDepartment === '')
            ? '__NO_DEPARTMENT_SELECTED__'
            : $selectedDepartment;

        $scope = $this->buildScope($user, $role, $scopeDepartment, $selectedClass, $selectedStudent, (int) ($user->id ?? 0));
        $studentIds = $this->resolveStudentIds($scope);

        $dailyRecap = $this->buildDailyRecap($studentIds, $weekStart, $weekEnd);
        $summary = $this->buildSummary($studentIds, $weekStart, $weekEnd);
        $ethos = $this->buildEthos($studentIds, $weekStart, $weekEnd);
        $violation = $this->buildViolationRecap($studentIds, $weekStart, $weekEnd);
        $analytics = $this->buildActionableAnalytics($studentIds, $weekStart, $weekEnd);

        $weeklyValidation = WeeklyValidation::query()
            ->whereDate('week_start', $weekStart->toDateString())
            ->whereDate('week_end', $weekEnd->toDateString())
            ->where('department_name', $scope['department_name'])
            ->where('class_name', $scope['class_name'])
            ->first();

        $instructorWeeklyNote = $weeklyValidation?->instruktur_note;
        $instructorWeeklyNoteClass = null;
        if (trim((string) $instructorWeeklyNote) === '' && in_array($role, ['kajur', 'superadmin'], true)) {
            $fallbackNoteQuery = WeeklyValidation::query()
                ->whereDate('week_start', $weekStart->toDateString())
                ->whereDate('week_end', $weekEnd->toDateString())
                ->where('department_name', $scope['department_name'])
                ->whereNotNull('instruktur_note');

            if (! empty($scope['class_name'])) {
                $fallbackNoteQuery->where('class_name', $scope['class_name']);
            }

            $fallbackNote = $fallbackNoteQuery
                ->orderByDesc('noted_instruktur_at')
                ->orderByDesc('id')
                ->first();

            if ($fallbackNote) {
                $instructorWeeklyNote = $fallbackNote->instruktur_note;
                $instructorWeeklyNoteClass = $fallbackNote->class_name;
            }
        }

        $historyPerPageOptions = [10, 20, 50, 100];
        $historyPerPage = (int) $request->integer('history_per_page', 10);
        if (! in_array($historyPerPage, $historyPerPageOptions, true)) {
            $historyPerPage = 10;
        }

        $historyQuery = WeeklyValidation::query()
            ->with(['validator', 'approverKajur'])
            ->orderByDesc('week_start')
            ->orderByDesc('id');
        if (! empty($scope['department_name']) && $scope['department_name'] !== '__NO_DEPARTMENT_SELECTED__') {
            $historyQuery->where('department_name', $scope['department_name']);
        }
        if (! empty($scope['class_name'])) {
            $historyQuery->where('class_name', $scope['class_name']);
        }
        $validationHistory = $historyQuery
            ->paginate($historyPerPage, ['*'], 'history_page')
            ->appends($request->query());

        $guidanceRows = collect();
        if (in_array($role, ['pembimbing_pkl', 'instruktur', 'kajur'], true)) {
            $guidanceQuery = StudentGuidanceNote::query()
                ->with([
                    'student:id,name,nis,class_name,department_name',
                    'mentor1:id,name',
                    'mentor2:id,name',
                ])
                ->whereDate('guidance_date', $selectedDate)
                ->orderBy('guidance_date')
                ->orderBy('id');

            if ($role === 'pembimbing_pkl') {
                $guidanceQuery->where(function ($query) use ($user): void {
                    $query->where('mentor1_user_id', (int) $user->id)
                        ->orWhere('mentor2_user_id', (int) $user->id)
                        ->orWhereExists(function ($sub) use ($user): void {
                            $sub->select(DB::raw(1))
                                ->from('student_mentor_assignments')
                                ->whereColumn('student_mentor_assignments.student_user_id', 'student_guidance_notes.student_user_id')
                                ->where('student_mentor_assignments.mentor_user_id', (int) $user->id)
                                ->whereIn('student_mentor_assignments.mentor_role', ['pembimbing_pkl', 'instruktur']);
                        })
                        ->orWhereHas('student', fn ($student) => $student->where('pembimbing_user_id', (int) $user->id));
                });
            } elseif ($role === 'instruktur') {
                $guidanceQuery->where(function ($query) use ($user): void {
                    $query->where('mentor1_user_id', (int) $user->id)
                        ->orWhere('mentor2_user_id', (int) $user->id)
                        ->orWhereExists(function ($sub) use ($user): void {
                            $sub->select(DB::raw(1))
                                ->from('student_mentor_assignments')
                                ->whereColumn('student_mentor_assignments.student_user_id', 'student_guidance_notes.student_user_id')
                                ->where('student_mentor_assignments.mentor_user_id', (int) $user->id)
                                ->whereIn('student_mentor_assignments.mentor_role', ['instruktur', 'pembimbing_pkl']);
                        });

                    // Fallback: instruktur baru tanpa assignment eksplisit tetap bisa melihat
                    // catatan siswa dalam scope jurusan/kelas akunnya.
                    $departmentName = trim((string) ($user->department_name ?? ''));
                    $className = trim((string) ($user->class_name ?? ''));
                    if ($departmentName !== '' || $className !== '') {
                        $query->orWhereHas('student', function ($student) use ($departmentName, $className): void {
                            if ($departmentName !== '') {
                                $student->where('department_name', $departmentName);
                            }
                            if ($className !== '') {
                                $student->where('class_name', $className);
                            }
                        });
                    }
                });
            } else {
                $guidanceQuery->whereHas('student', function ($query) use ($selectedDepartment): void {
                    if ($selectedDepartment !== '') {
                        $query->where('department_name', $selectedDepartment);
                    }
                });
            }

            $guidanceRows = $guidanceQuery->get();
        }

        return [
            'role' => $role,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'selectedDate' => $selectedDate,
            'selectedDepartment' => $selectedDepartment,
            'selectedClass' => $selectedClass,
            'selectedStudent' => $selectedStudent,
            'departmentOptions' => $departmentOptions,
            'classOptions' => $classOptions,
            'studentOptions' => $studentOptions,
            'summary' => $summary,
            'dailyRecap' => $dailyRecap,
            'ethos' => $ethos,
            'violation' => $violation,
            'analytics' => $analytics,
            'weeklyValidation' => $weeklyValidation,
            'instructorWeeklyNote' => $instructorWeeklyNote,
            'instructorWeeklyNoteClass' => $instructorWeeklyNoteClass,
            'validationHistory' => $validationHistory,
            'historyPerPage' => $historyPerPage,
            'historyPerPageOptions' => $historyPerPageOptions,
            'guidanceRows' => $guidanceRows,
        ];
    }

    private function normalizeRole(string $role): string
    {
        $raw = strtolower(trim($role));
        return match ($raw) {
            'pembimbing', 'pembimbing sekolah', 'pembimbing_sekolah' => 'pembimbing_pkl',
            'wakasek', 'wakil kepala sekolah', 'wakil_kepala_sekolah' => 'wakil_kepsek',
            default => $raw,
        };
    }

    public function approve(Request $request): RedirectResponse
    {
        return $this->saveValidation($request, 'approved');
    }

    public function approveById(Request $request, WeeklyValidation $weeklyValidation): RedirectResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        if ($role === 'kajur') {
            $kajurDepartment = trim((string) ($user->department_name ?? ''));
            abort_if(
                $kajurDepartment === '' || (string) ($weeklyValidation->department_name ?? '') !== $kajurDepartment,
                403,
                'Kajur hanya bisa validasi data jurusannya sendiri.'
            );
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $weeklyValidation->update([
            'status' => 'approved',
            'note' => $validated['note'] ?? $weeklyValidation->note,
            'kajur_note' => $validated['note'] ?? $weeklyValidation->kajur_note,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'approved_by_kajur' => $user->id,
            'approved_at' => now(),
            'noted_by_kajur' => $user->id,
            'noted_kajur_at' => now(),
        ]);

        ValidationLogger::log(
            $user,
            'weekly_validation',
            (int) $weeklyValidation->id,
            'approve_weekly_by_id',
            $validated['note'] ?? null,
            [
                'week_start' => optional($weeklyValidation->week_start)->toDateString(),
                'week_end' => optional($weeklyValidation->week_end)->toDateString(),
                'department_name' => $weeklyValidation->department_name,
                'class_name' => $weeklyValidation->class_name,
            ]
        );

        return back()->with('success', 'Validasi mingguan berhasil disetujui.');
    }

    public function revise(Request $request): RedirectResponse
    {
        return $this->saveValidation($request, 'revisi');
    }

    public function saveNote(Request $request): RedirectResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        abort_if(! in_array($role, ['instruktur', 'kajur', 'pembimbing_pkl', 'superadmin'], true), 403, 'Role tidak diizinkan mengisi catatan mingguan.');

        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'kelas' => ['nullable', 'string', 'max:100'],
            'note' => ['required', 'string', 'max:1000'],
            'note_role' => ['nullable', 'in:instruktur,kajur,pembimbing_pkl'],
        ]);

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);
        $department = trim((string) ($validated['jurusan'] ?? ''));
        $className = trim((string) ($validated['kelas'] ?? ''));

        if (in_array($role, ['kajur', 'instruktur', 'pembimbing_pkl'], true)) {
            $department = trim((string) ($user->department_name ?? ''));
            $className = '';
        }

        $noteRole = $role;
        if ($role === 'superadmin') {
            $noteRole = (string) ($validated['note_role'] ?? 'instruktur');
        }
        if (! in_array($noteRole, ['instruktur', 'kajur', 'pembimbing_pkl'], true)) {
            $noteRole = 'instruktur';
        }

        $weeklyValidation = WeeklyValidation::firstOrCreate(
            [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'department_name' => $department === '' ? null : $department,
                'class_name' => $className === '' ? null : $className,
            ],
            [
                'status' => 'pending',
            ]
        );

        if (in_array($noteRole, ['kajur', 'pembimbing_pkl'], true)) {
            $ownerField = $noteRole === 'kajur' ? 'noted_by_kajur' : 'noted_by_instruktur';
            $noteField = $noteRole === 'kajur' ? 'kajur_note' : 'instruktur_note';
            $existingOwner = (int) ($weeklyValidation->{$ownerField} ?? 0);
            $existingNote = trim((string) ($weeklyValidation->{$noteField} ?? ''));
            if ($existingOwner !== 0 && $existingOwner !== (int) $user->id && $existingNote !== '') {
                return back()->withErrors([
                    'note' => $noteRole === 'kajur'
                        ? 'Kajur hanya boleh membuat 1 catatan dalam satu minggu (Senin-Minggu).'
                        : 'Pembimbing sekolah hanya boleh membuat 1 catatan dalam satu minggu (Senin-Minggu).',
                ]);
            }
        }

        $note = trim((string) $validated['note']);
        if ($noteRole === 'kajur') {
            $weeklyValidation->update([
                'kajur_note' => $note,
                'noted_by_kajur' => $user->id,
                'noted_kajur_at' => now(),
            ]);
        } elseif ($noteRole === 'pembimbing_pkl') {
            $weeklyValidation->update([
                'instruktur_note' => $note,
                'noted_by_instruktur' => $user->id,
                'noted_instruktur_at' => now(),
            ]);
        } else {
            $weeklyValidation->update([
                'instruktur_note' => $note,
                'noted_by_instruktur' => $user->id,
                'noted_instruktur_at' => now(),
            ]);
        }

        ValidationLogger::log(
            $user,
            'weekly_validation',
            (int) $weeklyValidation->id,
            $noteRole === 'kajur'
                ? 'save_weekly_note_kajur'
                : ($noteRole === 'pembimbing_pkl' ? 'save_weekly_note_pembimbing' : 'save_weekly_note_instruktur'),
            $note,
            [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'department_name' => $department === '' ? null : $department,
                'class_name' => $className === '' ? null : $className,
            ]
        );

        return back()->with('success', 'Catatan mingguan berhasil disimpan.');
    }

    public function deleteNote(Request $request): RedirectResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');
        abort_if(! in_array($role, ['instruktur', 'kajur', 'pembimbing_pkl', 'superadmin'], true), 403, 'Role tidak diizinkan menghapus catatan mingguan.');

        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'kelas' => ['nullable', 'string', 'max:100'],
            'note_role' => ['nullable', 'in:instruktur,kajur,pembimbing_pkl'],
        ]);

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);
        $department = trim((string) ($validated['jurusan'] ?? ''));
        $className = trim((string) ($validated['kelas'] ?? ''));

        if (in_array($role, ['kajur', 'instruktur', 'pembimbing_pkl'], true)) {
            $department = trim((string) ($user->department_name ?? ''));
            $className = '';
        }

        $noteRole = $role === 'superadmin'
            ? (string) ($validated['note_role'] ?? 'instruktur')
            : $role;

        if (! in_array($noteRole, ['instruktur', 'kajur', 'pembimbing_pkl'], true)) {
            $noteRole = 'instruktur';
        }

        $weeklyValidation = WeeklyValidation::query()
            ->whereDate('week_start', $weekStart->toDateString())
            ->whereDate('week_end', $weekEnd->toDateString())
            ->where('department_name', $department === '' ? null : $department)
            ->where('class_name', $className === '' ? null : $className)
            ->first();

        if (! $weeklyValidation) {
            return back()->withErrors(['note' => 'Catatan mingguan tidak ditemukan.']);
        }

        if ($noteRole === 'kajur') {
            abort_if($role !== 'superadmin' && (int) ($weeklyValidation->noted_by_kajur ?? 0) !== (int) $user->id, 403, 'Tidak berwenang menghapus catatan ini.');
            $weeklyValidation->update([
                'kajur_note' => null,
                'noted_by_kajur' => null,
                'noted_kajur_at' => null,
            ]);
        } else {
            abort_if($role !== 'superadmin' && (int) ($weeklyValidation->noted_by_instruktur ?? 0) !== (int) $user->id, 403, 'Tidak berwenang menghapus catatan ini.');
            $weeklyValidation->update([
                'instruktur_note' => null,
                'noted_by_instruktur' => null,
                'noted_instruktur_at' => null,
            ]);
        }

        return back()->with('success', 'Catatan mingguan berhasil dihapus.');
    }

    private function saveValidation(Request $request, string $status): RedirectResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        $validated = $request->validate([
            'week_start' => ['required', 'date'],
            'jurusan' => ['nullable', 'string', 'max:100'],
            'kelas' => ['nullable', 'string', 'max:100'],
            'note' => [$status === 'revisi' ? 'required' : 'nullable', 'string', 'max:1000'],
        ]);

        $weekStart = Carbon::parse($validated['week_start'])->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);
        $department = trim((string) ($validated['jurusan'] ?? ''));
        $className = trim((string) ($validated['kelas'] ?? ''));

        if (in_array($role, ['kajur', 'instruktur'], true)) {
            $department = trim((string) ($user->department_name ?? ''));
            $className = '';
        }

        $payload = [
            'status' => $status,
            'note' => $validated['note'] ?? null,
            'validated_by' => $user->id,
            'validated_at' => now(),
            'approved_by_kajur' => in_array($role, ['kajur', 'superadmin'], true) ? $user->id : null,
            'approved_at' => in_array($role, ['kajur', 'superadmin'], true) ? now() : null,
        ];
        if (in_array($role, ['kajur', 'superadmin'], true)) {
            $payload['kajur_note'] = $validated['note'] ?? null;
            $payload['noted_by_kajur'] = $user->id;
            $payload['noted_kajur_at'] = now();
        } elseif ($role === 'instruktur') {
            $payload['instruktur_note'] = $validated['note'] ?? null;
            $payload['noted_by_instruktur'] = $user->id;
            $payload['noted_instruktur_at'] = now();
        }

        $weeklyValidation = WeeklyValidation::updateOrCreate(
            [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'department_name' => $department === '' ? null : $department,
                'class_name' => $className === '' ? null : $className,
            ],
            $payload
        );

        ValidationLogger::log(
            $user,
            'weekly_validation',
            (int) $weeklyValidation->id,
            $status === 'approved' ? 'approve_weekly' : 'revise_weekly',
            $validated['note'] ?? null,
            [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'department_name' => $department === '' ? null : $department,
                'class_name' => $className === '' ? null : $className,
                'status' => $status,
            ]
        );

        return back()->with('success', $status === 'approved'
            ? 'Validasi mingguan berhasil disetujui.'
            : 'Validasi mingguan dikembalikan untuk perbaikan.');
    }

    private function buildScope(User $actor, string $role, string $department, string $className, string $studentId, int $actorUserId): array
    {
        return [
            'department_name' => $department === '' ? null : $department,
            'class_name' => $className === '' ? null : $className,
            'student_id' => $studentId === '' ? null : (int) $studentId,
            'actor_role' => $role,
            'actor_user_id' => $actorUserId,
        ];
    }

    private function resolveStudentIds(array $scope): array
    {
        if (($scope['department_name'] ?? null) === '__NO_DEPARTMENT_SELECTED__') {
            return [];
        }

        $actorRole = (string) ($scope['actor_role'] ?? '');
        if (in_array($actorRole, ['pembimbing_pkl', 'instruktur'], true)) {
            $actor = User::query()->find((int) ($scope['actor_user_id'] ?? 0));
            if (! $actor) {
                return [];
            }
            $assignedIds = StudentMentorScopeResolver::allowedStudentIds($actor) ?? [];
            if ($assignedIds === []) {
                return [];
            }

            $query = User::query()->where('role', 'siswa')->whereIn('id', $assignedIds);
            if (! empty($scope['department_name'])) {
                $query->where('department_name', $scope['department_name']);
            }
            if (! empty($scope['class_name'])) {
                $query->where('class_name', $scope['class_name']);
            }
            if (! empty($scope['student_id'])) {
                $query->where('id', (int) $scope['student_id']);
            }

            return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $query = User::query()->where('role', 'siswa');
        if (! empty($scope['department_name'])) {
            $query->where('department_name', $scope['department_name']);
        }
        if (! empty($scope['class_name'])) {
            $query->where('class_name', $scope['class_name']);
        }
        if (! empty($scope['student_id'])) {
            $query->where('id', (int) $scope['student_id']);
        }
        return $query->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    private function buildSummary(array $studentIds, Carbon $start, Carbon $end): array
    {
        if ($studentIds === []) {
            return ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpha' => 0, 'pending' => 0, 'total' => 0];
        }

        $att = Attendance::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);
        $lv = LeaveRequest::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('request_date', [$start->toDateString(), $end->toDateString()]);

        // Weekly KPI "hadir" is counted per unique student (not per day).
        $hadir = (clone $att)->where('status', 'hadir')->distinct('user_id')->count('user_id');
        $izin = (clone $lv)->whereIn('status', ['approved', 'izin_approved'])->where('type', 'izin')->count();
        $sakit = (clone $lv)->whereIn('status', ['approved', 'sakit_approved'])->where('type', 'sakit')->count();
        $alpha = (clone $att)->where(function ($query): void {
            $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
        })->count() + (clone $lv)->where(function ($query): void {
            $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::LEAVE_REJECTS);
        })->count();
        $pending = (clone $att)->whereIn('status', WorkflowState::ATTENDANCE_PENDING)->count();

        return [
            'hadir' => $hadir,
            'izin' => $izin,
            'sakit' => $sakit,
            'alpha' => $alpha,
            'pending' => $pending,
            'total' => $hadir + $izin + $sakit + $alpha + $pending,
        ];
    }

    private function buildDailyRecap(array $studentIds, Carbon $start, Carbon $end): array
    {
        if ($studentIds === []) {
            return [];
        }

        $rows = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $date = $cursor->toDateString();
            $att = Attendance::query()
                ->whereIn('user_id', $studentIds)
                ->whereDate('attendance_date', $date);
            $lv = LeaveRequest::query()
                ->whereIn('user_id', $studentIds)
                ->whereDate('request_date', $date);

            $rows[] = [
                'date' => $date,
                'hadir' => (clone $att)->where('status', 'hadir')->count(),
                'izin' => (clone $lv)->whereIn('status', ['approved', 'izin_approved'])->where('type', 'izin')->count(),
                'sakit' => (clone $lv)->whereIn('status', ['approved', 'sakit_approved'])->where('type', 'sakit')->count(),
                'alpha' => (clone $att)->where(function ($query): void {
                    $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
                })->count(),
                'pending' => (clone $att)->whereIn('status', WorkflowState::ATTENDANCE_PENDING)->count(),
            ];
            $cursor->addDay();
        }

        return $rows;
    }

    private function buildEthos(array $studentIds, Carbon $start, Carbon $end): array
    {
        if ($studentIds === []) {
            return [
                'count' => 0,
                'keramahan_baik' => 0,
                'senyum_baik' => 0,
                'penampilan_baik' => 0,
                'komunikasi_baik' => 0,
                'realisasi_kerja_baik' => 0,
            ];
        }

        $query = Assessment::query()
            ->whereHas('attendance', function ($attendanceQuery) use ($studentIds, $start, $end): void {
                $attendanceQuery
                    ->whereIn('user_id', $studentIds)
                    ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()]);
            });

        return [
            'count' => (clone $query)->count(),
            'keramahan_baik' => (clone $query)->where('keramahan_baik', true)->count(),
            'senyum_baik' => (clone $query)->where('senyum_baik', true)->count(),
            'penampilan_baik' => (clone $query)->where('penampilan_baik', true)->count(),
            'komunikasi_baik' => (clone $query)->where('komunikasi_baik', true)->count(),
            'realisasi_kerja_baik' => (clone $query)->where('realisasi_kerja_baik', true)->count(),
        ];
    }

    private function buildViolationRecap(array $studentIds, Carbon $start, Carbon $end): array
    {
        if ($studentIds === []) {
            return ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0];
        }

        $alphaA = Attendance::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query): void {
                $query->where('status', 'alpha')->orWhereIn('status', WorkflowState::ATTENDANCE_REJECTS);
            })->count();

        $approvedLeaveD = LeaveRequest::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('request_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['approved', 'izin_approved', 'sakit_approved'])
            ->count();

        $withoutDescB = LeaveRequest::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('request_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', WorkflowState::LEAVE_REJECTS)
            ->count();

        $leavePlaceC = AttendanceException::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query): void {
                $query->where('exception_type', 'like', '%meninggalkan%')
                    ->orWhere('exception_type', 'like', '%leave%');
            })
            ->count();

        $lateE = AttendanceException::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->where(function ($query): void {
                $query->where('exception_type', 'like', '%late%')
                    ->orWhere('exception_type', 'like', '%telat%')
                    ->orWhere('exception_type', 'like', '%terlambat%');
            })
            ->count();

        return [
            'A' => $alphaA,
            'B' => $withoutDescB,
            'C' => $leavePlaceC,
            'D' => $approvedLeaveD,
            'E' => $lateE,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildActionableAnalytics(array $studentIds, Carbon $start, Carbon $end): array
    {
        if ($studentIds === []) {
            return [
                'alphaTop' => [],
                'pendingTop' => [],
                'violationTop' => [],
                'bestTop' => [],
                'worstTop' => [],
                'departmentRecap' => [],
                'locationRecap' => [],
                'alerts' => [
                    'alpha_over_3' => [],
                    'missing_report_over_2' => [],
                    'pending_over_2_days' => [],
                ],
            ];
        }

        $students = User::query()
            ->whereIn('id', $studentIds)
            ->get(['id', 'name', 'nis', 'class_name', 'department_name', 'pkl_location_id']);

        $locationNames = PklLocation::query()
            ->whereIn('id', $students->pluck('pkl_location_id')->filter()->unique()->values())
            ->pluck('name', 'id');

        $attendances = Attendance::query()
            ->with('report')
            ->whereIn('user_id', $studentIds)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $exceptions = AttendanceException::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('event_date', [$start->toDateString(), $end->toDateString()])
            ->get(['user_id', 'exception_type']);

        $leaveRows = LeaveRequest::query()
            ->whereIn('user_id', $studentIds)
            ->whereBetween('request_date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('status', ['approved', 'izin_approved'])
            ->get(['user_id']);

        $metrics = [];
        foreach ($students as $student) {
            $metrics[(int) $student->id] = [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'nis' => (string) ($student->nis ?? '-'),
                'class_name' => (string) ($student->class_name ?? '-'),
                'department_name' => (string) ($student->department_name ?? '-'),
                'location_name' => (string) ($locationNames[(int) ($student->pkl_location_id ?? 0)] ?? '-'),
                'hadir' => 0,
                'alpha' => 0,
                'izin' => 0,
                'pending' => 0,
                'pending_days' => [],
                'missing_report_days' => [],
                'violation_c' => 0,
                'violation_e' => 0,
                'outside_radius' => 0,
            ];
        }

        foreach ($attendances as $attendance) {
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

        foreach ($exceptions as $exception) {
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

        foreach ($leaveRows as $leave) {
            $uid = (int) $leave->user_id;
            if (! isset($metrics[$uid])) {
                continue;
            }
            $metrics[$uid]['izin']++;
        }

        $rows = collect(array_values($metrics))->map(function (array $row): array {
            $row['pending_days_count'] = count($row['pending_days']);
            $row['missing_report_days_count'] = count($row['missing_report_days']);
            $row['violation_total'] = (int) $row['violation_c'] + (int) $row['violation_e'];
            $row['best_score'] = ((int) $row['hadir'] * 10) - ((int) $row['alpha'] * 8) - ((int) $row['pending_days_count'] * 4) - ((int) $row['violation_total'] * 5);
            $row['risk_score'] = ((int) $row['alpha'] * 10) + ((int) $row['pending_days_count'] * 6) + ((int) $row['violation_total'] * 7) + ((int) $row['outside_radius'] * 4);
            return $row;
        });

        $alphaTop = $rows->where('alpha', '>', 0)->sortByDesc('alpha')->take(10)->values()->all();
        $pendingTop = $rows->where('pending_days_count', '>', 0)->sortByDesc('pending_days_count')->take(10)->values()->all();
        $violationTop = $rows->where('violation_total', '>', 0)->sortByDesc('violation_total')->take(10)->values()->all();
        $bestTop = $rows->sortByDesc('best_score')->take(10)->values()->all();
        $worstTop = $rows->sortByDesc('risk_score')->take(10)->values()->all();

        $departmentRecap = $rows
            ->groupBy('department_name')
            ->map(function ($items, $department) {
                $hadir = (int) $items->sum('hadir');
                $alpha = (int) $items->sum('alpha');
                $izin = (int) $items->sum('izin');
                return [
                    'department_name' => (string) $department,
                    'hadir' => $hadir,
                    'alpha' => $alpha,
                    'izin' => $izin,
                    'ranking_score' => $hadir - ($alpha * 2),
                ];
            })
            ->sortByDesc('ranking_score')
            ->values()
            ->all();

        $locationRecap = $rows
            ->groupBy('location_name')
            ->map(function ($items, $location) {
                $alpha = (int) $items->sum('alpha');
                $issues = (int) $items->sum('violation_total') + (int) $items->sum('pending_days_count');
                return [
                    'location_name' => (string) $location,
                    'student_count' => (int) $items->count(),
                    'alpha' => $alpha,
                    'issues' => $issues,
                ];
            })
            ->sortByDesc('issues')
            ->values()
            ->all();

        $alerts = [
            'alpha_over_3' => $rows->where('alpha', '>', 3)->values()->all(),
            'missing_report_over_2' => $rows->where('missing_report_days_count', '>', 2)->values()->all(),
            'pending_over_2_days' => $rows->where('pending_days_count', '>', 2)->values()->all(),
        ];

        return [
            'alphaTop' => $alphaTop,
            'pendingTop' => $pendingTop,
            'violationTop' => $violationTop,
            'bestTop' => $bestTop,
            'worstTop' => $worstTop,
            'departmentRecap' => $departmentRecap,
            'locationRecap' => $locationRecap,
            'alerts' => $alerts,
        ];
    }

    private function resolveWeekStart(string $weekStart): Carbon
    {
        if ($weekStart !== '') {
            return Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY);
        }

        return now()->startOfWeek(Carbon::MONDAY);
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
    private function availableClasses(?string $departmentName): array
    {
        $query = User::query()
            ->where('role', 'siswa')
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '');

        if ($departmentName !== null && trim($departmentName) !== '') {
            $query->where('department_name', $departmentName);
        }

        return $query
            ->orderBy('class_name')
            ->distinct()
            ->pluck('class_name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{id:int,name:string,nis:string,class_name:?string,department_name:?string}>
     */
    private function availableStudents(?string $departmentName, ?string $className, ?int $pembimbingId = null): array
    {
        $query = User::query()->where('role', 'siswa');

        if ($departmentName !== null && trim($departmentName) !== '') {
            $query->where('department_name', $departmentName);
        }

        if ($className !== null && trim($className) !== '') {
            $query->where('class_name', $className);
        }

        if ($pembimbingId !== null) {
            $query->where('pembimbing_user_id', $pembimbingId);
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'nis', 'class_name', 'department_name'])
            ->map(fn (User $student) => [
                'id' => (int) $student->id,
                'name' => (string) $student->name,
                'nis' => (string) ($student->nis ?? '-'),
                'class_name' => $student->class_name,
                'department_name' => $student->department_name,
            ])
            ->values()
            ->all();
    }
}
