<?php

namespace App\Http\Controllers;

use App\Models\StudentGuidanceNote;
use App\Models\StudentMentorAssignment;
use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentGuidanceNoteController extends Controller
{
    public function studentIndex(Request $request): View
    {
        $user = $request->user();
        $appTz = config('app.timezone', 'Asia/Jakarta');
        $isFriday = now($appTz)->dayOfWeekIso === Carbon::FRIDAY;
        $this->markExpiredAsAlpha();

        $allNotes = StudentGuidanceNote::query()
            ->where('student_user_id', (int) $user->id)
            ->orderByDesc('guidance_date')
            ->get();

        $weekOptions = $allNotes
            ->map(function (StudentGuidanceNote $note): ?array {
                if (! $note->guidance_date) {
                    return null;
                }
                $start = $note->guidance_date->copy()->startOfWeek(Carbon::MONDAY);
                $end = $start->copy()->endOfWeek(Carbon::SUNDAY);
                return [
                    'value' => $start->toDateString(),
                    'label' => $start->format('d M Y').' - '.$end->format('d M Y'),
                ];
            })
            ->filter()
            ->unique('value')
            ->sortByDesc('value')
            ->values();

        $selectedWeekStart = trim((string) $request->string('week_start')->toString());
        if ($selectedWeekStart === '') {
            $selectedWeekStart = now($appTz)->startOfWeek(Carbon::MONDAY)->toDateString();
        }

        $notes = $allNotes->filter(function (StudentGuidanceNote $note) use ($selectedWeekStart): bool {
            if (! $note->guidance_date) {
                return false;
            }
            return $note->guidance_date->copy()->startOfWeek(Carbon::MONDAY)->toDateString() === $selectedWeekStart;
        })->values();

        $attendanceMap = Attendance::query()
            ->where('user_id', (int) $user->id)
            ->whereIn('attendance_date', $notes->pluck('guidance_date')->filter()->values()->all())
            ->get(['attendance_date', 'status'])
            ->keyBy(fn ($row) => optional($row->attendance_date)->toDateString());

        return view('guidance.student', [
            'title' => 'Catatan Bimbingan',
            'notes' => $notes,
            'isFriday' => $isFriday,
            'attendanceMap' => $attendanceMap,
            'appTimezone' => $appTz,
            'weekOptions' => $weekOptions,
            'selectedWeekStart' => $selectedWeekStart,
        ]);
    }

    public function studentStore(Request $request): RedirectResponse
    {
        $appTz = config('app.timezone', 'Asia/Jakarta');
        $isFriday = now($appTz)->dayOfWeekIso === Carbon::FRIDAY;
        if (! $isFriday) {
            return back()->with('error', 'Catatan bimbingan hanya bisa diisi pada hari Jumat.');
        }

        $validated = $request->validate([
            'student_note' => ['required', 'string', 'max:2000'],
        ]);

        $student = $request->user();
        $date = now($appTz)->toDateString();
        [$mentor1, $mentor2] = $this->resolveMentors((int) $student->id);

        StudentGuidanceNote::updateOrCreate(
            [
                'student_user_id' => (int) $student->id,
                'guidance_date' => $date,
            ],
            [
                'student_note' => trim((string) $validated['student_note']),
                'student_submitted_at' => now(config('app.timezone', 'Asia/Jakarta')),
                'mentor1_user_id' => $mentor1,
                'mentor2_user_id' => $mentor2,
                'mentor1_status' => 'pending',
                'mentor2_status' => 'pending',
                'wakil_status' => 'pending',
                'final_attendance_status' => 'pending',
            ]
        );

        return back()->with('success', 'Catatan bimbingan berhasil disimpan.');
    }

    public function mentorIndex(Request $request): View
    {
        $this->markExpiredAsAlpha();
        $actor = $request->user();
        $uid = (int) $actor->id;
        $role = strtolower(trim((string) ($actor->role ?? '')));
        $departmentName = trim((string) ($actor->department_name ?? ''));
        $className = trim((string) ($actor->class_name ?? ''));

        $notes = StudentGuidanceNote::query()
            ->with('student:id,name,nis,class_name')
            ->where(function ($q) use ($uid, $role, $departmentName, $className): void {
                $q->where('mentor1_user_id', $uid)
                    ->orWhere('mentor2_user_id', $uid)
                    ->orWhereExists(function ($sub) use ($uid): void {
                        $sub->selectRaw('1')
                            ->from('student_mentor_assignments')
                            ->whereColumn('student_mentor_assignments.student_user_id', 'student_guidance_notes.student_user_id')
                            ->where('student_mentor_assignments.mentor_user_id', $uid)
                            ->whereIn('student_mentor_assignments.mentor_role', ['pembimbing_pkl', 'instruktur']);
                    })
                    ->orWhereHas('student', fn ($student) => $student->where('pembimbing_user_id', $uid));

                if ($role === 'instruktur' && ($departmentName !== '' || $className !== '')) {
                    $q->orWhereHas('student', function ($student) use ($departmentName, $className): void {
                        if ($departmentName !== '') {
                            $student->where('department_name', $departmentName);
                        }
                        if ($className !== '') {
                            $student->where('class_name', $className);
                        }
                    });
                }
            })
            ->whereNotNull('student_submitted_at')
            ->orderByDesc('guidance_date')
            ->get();

        return view('guidance.mentor', ['title' => 'Validasi Catatan Bimbingan', 'notes' => $notes]);
    }

    public function mentorValidate(Request $request, StudentGuidanceNote $note): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
            'mentor_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $uid = (int) $request->user()->id;
        $status = (bool) $validated['approved'] ? 'approved' : 'rejected';
        $mentorNote = trim((string) ($validated['mentor_note'] ?? ''));

        if ((int) $note->mentor1_user_id === $uid) {
            $note->update([
                'mentor1_status' => $status,
                'mentor1_note' => $mentorNote !== '' ? $mentorNote : null,
                'mentor1_validated_by' => $uid,
                'mentor1_validated_at' => now(config('app.timezone', 'Asia/Jakarta')),
            ]);
        } elseif ((int) $note->mentor2_user_id === $uid) {
            $note->update([
                'mentor2_status' => $status,
                'mentor2_note' => $mentorNote !== '' ? $mentorNote : null,
                'mentor2_validated_by' => $uid,
                'mentor2_validated_at' => now(config('app.timezone', 'Asia/Jakarta')),
            ]);
        } else {
            $assignment = StudentMentorAssignment::query()
                ->where('student_user_id', (int) $note->student_user_id)
                ->where('mentor_user_id', $uid)
                ->whereIn('mentor_role', ['pembimbing_pkl', 'instruktur'])
                ->first(['mentor_role']);

            $isLegacyPembimbing = (int) ($note->student?->pembimbing_user_id ?? 0) === $uid;
            $isScopedInstructor = $this->isScopedInstructorAllowed($request, $note);

            if (! $assignment && ! $isLegacyPembimbing && ! $isScopedInstructor) {
                abort(403);
            }

            $targetSlot = $this->resolveMentorTargetSlot($note, $uid, $isLegacyPembimbing);

            $note->update([
                $targetSlot.'_user_id' => $uid,
                $targetSlot.'_status' => $status,
                $targetSlot.'_note' => $mentorNote !== '' ? $mentorNote : null,
                $targetSlot.'_validated_by' => $uid,
                $targetSlot.'_validated_at' => now(config('app.timezone', 'Asia/Jakarta')),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Validasi pembimbing tersimpan.',
            ]);
        }

        return back()->with('success', 'Validasi pembimbing tersimpan.');
    }

    private function isScopedInstructorAllowed(Request $request, StudentGuidanceNote $note): bool
    {
        $actor = $request->user();
        if (! $actor || strtolower(trim((string) ($actor->role ?? ''))) !== 'instruktur') {
            return false;
        }

        $student = $note->student;
        if (! $student) {
            return false;
        }

        $actorDepartment = trim((string) ($actor->department_name ?? ''));
        $actorClass = trim((string) ($actor->class_name ?? ''));
        $studentDepartment = trim((string) ($student->department_name ?? ''));
        $studentClass = trim((string) ($student->class_name ?? ''));

        if ($actorDepartment === '' && $actorClass === '') {
            return false;
        }

        if ($actorDepartment !== '' && $actorDepartment !== $studentDepartment) {
            return false;
        }
        if ($actorClass !== '' && $actorClass !== $studentClass) {
            return false;
        }

        return true;
    }

    private function resolveMentorTargetSlot(StudentGuidanceNote $note, int $uid, bool $isLegacyPembimbing): string
    {
        if ((int) ($note->mentor1_user_id ?? 0) === $uid) {
            return 'mentor1';
        }

        if ((int) ($note->mentor2_user_id ?? 0) === $uid) {
            return 'mentor2';
        }

        $assignedMentorIds = StudentMentorAssignment::query()
            ->where('student_user_id', (int) $note->student_user_id)
            ->whereIn('mentor_role', ['pembimbing_pkl', 'instruktur'])
            ->orderBy('id')
            ->pluck('mentor_user_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $assignmentIndex = array_search($uid, $assignedMentorIds, true);
        if ($assignmentIndex === 0) {
            return 'mentor1';
        }
        if ($assignmentIndex === 1) {
            return 'mentor2';
        }

        if ($isLegacyPembimbing) {
            return 'mentor1';
        }

        return (int) ($note->mentor1_user_id ?? 0) === 0 ? 'mentor1' : 'mentor2';
    }

    public function kajurIndex(Request $request): View
    {
        $this->markExpiredAsAlpha();
        $department = trim((string) ($request->user()->department_name ?? ''));
        $notes = StudentGuidanceNote::query()
            ->with('student:id,name,nis,class_name,department_name')
            ->whereHas('student', fn ($q) => $q->where('department_name', $department))
            ->whereNotNull('student_submitted_at')
            ->orderByDesc('guidance_date')
            ->get();

        return view('guidance.kajur', ['title' => 'Catatan Bimbingan Jurusan', 'notes' => $notes]);
    }

    public function kajurNote(Request $request, StudentGuidanceNote $note): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'kajur_note' => ['nullable', 'string', 'max:1000'],
            'approved' => ['nullable', 'boolean'],
        ]);
        $approved = array_key_exists('approved', $validated)
            ? (bool) $validated['approved']
            : null;

        $note->update([
            'kajur_note' => trim((string) ($validated['kajur_note'] ?? '')) ?: null,
            'kajur_noted_by' => (int) $request->user()->id,
            'kajur_noted_at' => now(config('app.timezone', 'Asia/Jakarta')),
            'final_attendance_status' => $approved === null
                ? $note->final_attendance_status
                : ($approved ? 'hadir' : 'alpha'),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Catatan kajur disimpan.',
            ]);
        }

        return back()->with('success', 'Catatan kajur disimpan.');
    }

    public function wakilIndex(Request $request): View
    {
        $this->markExpiredAsAlpha();
        $q = trim((string) $request->string('q')->toString());
        $date = trim((string) $request->string('date')->toString());
        $status = trim((string) $request->string('status')->toString());

        if (! in_array($status, ['', 'pending', 'approved', 'rejected'], true)) {
            $status = '';
        }

        $notes = StudentGuidanceNote::query()
            ->with('student:id,name,nis,class_name')
            ->where(function ($q): void {
                $q->where('mentor1_status', 'approved')->orWhere('mentor2_status', 'approved');
            })
            ->when($date !== '', fn ($query) => $query->whereDate('guidance_date', $date))
            ->when($status !== '', fn ($query) => $query->where('wakil_status', $status))
            ->when($q !== '', function ($query) use ($q): void {
                $query->whereHas('student', function ($student) use ($q): void {
                    $student->where('name', 'like', '%'.$q.'%')
                        ->orWhere('nis', 'like', '%'.$q.'%')
                        ->orWhere('class_name', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('guidance_date')
            ->paginate(20)
            ->withQueryString();

        return view('guidance.wakil', [
            'title' => 'Validasi Kehadiran Wakil Kepsek',
            'notes' => $notes,
            'filters' => [
                'q' => $q,
                'date' => $date,
                'status' => $status,
            ],
        ]);
    }

    public function wakilValidate(Request $request, StudentGuidanceNote $note): RedirectResponse
    {
        $validated = $request->validate([
            'approved' => ['required', 'boolean'],
        ]);
        $approved = (bool) $validated['approved'];
        $note->update([
            'wakil_status' => $approved ? 'approved' : 'rejected',
            'wakil_note' => null,
            'wakil_validated_by' => (int) $request->user()->id,
            'wakil_validated_at' => now(config('app.timezone', 'Asia/Jakarta')),
            'final_attendance_status' => $approved ? 'hadir' : 'alpha',
        ]);

        return back()->with('success', 'Validasi akhir tersimpan.');
    }

    private function resolveMentors(int $studentId): array
    {
        $mentorIds = StudentMentorAssignment::query()
            ->where('student_user_id', $studentId)
            ->whereIn('mentor_role', ['pembimbing_pkl', 'instruktur'])
            ->orderBy('mentor_user_id')
            ->pluck('mentor_user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return [$mentorIds[0] ?? null, $mentorIds[1] ?? null];
    }

    private function markExpiredAsAlpha(): void
    {
        StudentGuidanceNote::query()
            ->whereDate('guidance_date', '<', now(config('app.timezone', 'Asia/Jakarta'))->toDateString())
            ->where('final_attendance_status', 'pending')
            ->where('wakil_status', 'pending')
            ->update([
                'final_attendance_status' => 'alpha',
            ]);
    }
}
