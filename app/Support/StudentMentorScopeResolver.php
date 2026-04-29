<?php

namespace App\Support;

use App\Models\MentorRoleScope;
use App\Models\StudentMentorAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentMentorScopeResolver
{
    /**
     * @return array<int, int>|null null means unrestricted (admin/superadmin/kajur fallback by dept elsewhere)
     */
    public static function allowedStudentIds(User $actor): ?array
    {
        $role = (string) ($actor->role ?? '');

        if (! in_array($role, ['pembimbing_pkl', 'instruktur'], true)) {
            return null;
        }

        $roleScope = MentorRoleScope::query()
            ->where('mentor_user_id', (int) $actor->id)
            ->where('mentor_role', $role)
            ->first();

        if ($roleScope?->all_students_in_department && filled($actor->department_name)) {
            return User::query()
                ->where('role', 'siswa')
                ->where('department_name', (string) $actor->department_name)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $explicit = StudentMentorAssignment::query()
            ->where('mentor_user_id', (int) $actor->id)
            ->where('mentor_role', $role)
            ->pluck('student_user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Transition fallback for existing pembimbing mapping.
        if ($explicit === [] && $role === 'pembimbing_pkl') {
            return User::query()
                ->where('role', 'siswa')
                ->where('pembimbing_user_id', (int) $actor->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        return $explicit;
    }

    public static function applyStudentScope(Builder $userScopedQuery, User $actor, string $userRelationPath = 'user'): void
    {
        $allowedStudentIds = self::allowedStudentIds($actor);
        if ($allowedStudentIds === null) {
            if ($actor->role === 'kajur' && filled($actor->department_name)) {
                $userScopedQuery->whereHas($userRelationPath, fn ($q) => $q->where('department_name', $actor->department_name));
            }
            return;
        }

        if ($allowedStudentIds === []) {
            $userScopedQuery->whereRaw('1 = 0');
            return;
        }

        $userScopedQuery->whereHas($userRelationPath, fn ($q) => $q->whereIn('id', $allowedStudentIds));
    }

    public static function canAccessStudent(User $actor, ?User $student): bool
    {
        if (! $student) {
            return false;
        }

        $allowedStudentIds = self::allowedStudentIds($actor);
        if ($allowedStudentIds === null) {
            if ($actor->role === 'kajur' && filled($actor->department_name)) {
                return (string) ($student->department_name ?? '') === (string) $actor->department_name;
            }
            return true;
        }

        return in_array((int) $student->id, $allowedStudentIds, true);
    }
}

