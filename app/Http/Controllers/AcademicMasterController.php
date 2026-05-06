<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\SchoolClass;
use App\Support\MenuAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class AcademicMasterController extends Controller
{
    public function index(Request $request): View
    {
        $supportsDeletedTab = Schema::hasColumn('departments', 'is_deleted')
            && Schema::hasColumn('school_classes', 'is_deleted');

        $departmentTab = $request->string('department_tab', 'active')->toString();
        $classTab = $request->string('class_tab', 'active')->toString();
        if (! in_array($departmentTab, ['active', 'deleted'], true)) {
            $departmentTab = 'active';
        }
        if (! in_array($classTab, ['active', 'deleted'], true)) {
            $classTab = 'active';
        }
        if (! $supportsDeletedTab) {
            $departmentTab = 'active';
            $classTab = 'active';
        }
        $isSuperadmin = (string) ($request->user()?->role ?? '') === 'superadmin';
        $hasDeletedTabAccess = $this->canAccessDeletedTab($request);
        if ($departmentTab === 'deleted' && ! $hasDeletedTabAccess) {
            $departmentTab = 'active';
        }
        if ($classTab === 'deleted' && ! $hasDeletedTabAccess) {
            $classTab = 'active';
        }

        $allowedPerPage = [10, 20, 50, 100];
        $departmentPerPage = (int) request()->integer('department_per_page', 10);
        $classPerPage = (int) request()->integer('class_per_page', 10);
        if (! in_array($departmentPerPage, $allowedPerPage, true)) {
            $departmentPerPage = 10;
        }
        if (! in_array($classPerPage, $allowedPerPage, true)) {
            $classPerPage = 10;
        }

        if ($supportsDeletedTab) {
            $departmentsQuery = $departmentTab === 'deleted'
                ? Department::query()->where('is_deleted', true)
                : Department::query()->where('is_deleted', false);
            $classesQuery = $classTab === 'deleted'
                ? SchoolClass::query()->where('is_deleted', true)->with('department')
                : SchoolClass::query()->where('is_deleted', false)->with('department');
            $departmentActiveCount = Department::query()->where('is_deleted', false)->count();
            $departmentDeletedCount = Department::query()->where('is_deleted', true)->count();
            $classActiveCount = SchoolClass::query()->where('is_deleted', false)->count();
            $classDeletedCount = SchoolClass::query()->where('is_deleted', true)->count();
            $allDepartments = Department::query()->where('is_deleted', false)->orderBy('name')->get();
        } else {
            $departmentsQuery = Department::query();
            $classesQuery = SchoolClass::query()->with('department');
            $departmentActiveCount = Department::query()->count();
            $departmentDeletedCount = 0;
            $classActiveCount = SchoolClass::query()->count();
            $classDeletedCount = 0;
            $allDepartments = Department::query()->orderBy('name')->get();
        }

        return view('masters.academic', [
            'title' => 'Tambah Jurusan & Kelas',
            'departmentTab' => $departmentTab,
            'classTab' => $classTab,
            'isSuperadmin' => $isSuperadmin,
            'hasDeletedTabAccess' => $hasDeletedTabAccess,
            'supportsDeletedTab' => $supportsDeletedTab,
            'departmentActiveCount' => $departmentActiveCount,
            'departmentDeletedCount' => $departmentDeletedCount,
            'classActiveCount' => $classActiveCount,
            'classDeletedCount' => $classDeletedCount,
            'allDepartments' => $allDepartments,
            'departments' => $departmentsQuery
                ->orderBy('name')
                ->paginate($departmentPerPage, ['*'], 'department_page')
                ->appends(request()->query()),
            'classes' => $classesQuery
                ->orderBy('name')
                ->paginate($classPerPage, ['*'], 'class_page')
                ->appends(request()->query()),
            'perPageOptions' => $allowedPerPage,
            'departmentPerPage' => $departmentPerPage,
            'classPerPage' => $classPerPage,
        ]);
    }

    public function storeDepartment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')],
        ]);

        Department::query()->create([
            'name' => trim((string) $data['name']),
        ]);

        return back()->with('success', 'Jurusan berhasil ditambahkan.');
    }

    public function updateDepartment(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('departments', 'name')->ignore($department->id)],
        ]);

        $department->update([
            'name' => trim((string) $data['name']),
        ]);

        return back()->with('success', 'Jurusan berhasil diperbarui.');
    }

    public function destroyDepartment(Department $department): RedirectResponse
    {
        $supportsDeletedTab = Schema::hasColumn('departments', 'is_deleted');

        $usedByClass = SchoolClass::query()
            ->where('department_id', (int) $department->id)
            ->when(
                Schema::hasColumn('school_classes', 'is_deleted'),
                fn ($query) => $query->where('is_deleted', false)
            )
            ->exists();
        if ($usedByClass) {
            return back()->with('error', 'Jurusan tidak bisa dihapus karena masih dipakai pada data kelas.');
        }

        if ($supportsDeletedTab) {
            $department->update(['is_deleted' => true]);
        } else {
            $department->delete();
        }

        return back()->with('success', 'Jurusan berhasil dihapus.');
    }

    public function restoreDepartment(Request $request, int $id): RedirectResponse
    {
        if (! Schema::hasColumn('departments', 'is_deleted')) {
            return back()->with('error', 'Fitur restore jurusan belum tersedia di database ini.');
        }
        if (! $this->canAccessDeletedTab($request)) {
            return back()->with('error', 'Anda tidak punya akses ke tab Deleted.');
        }

        $department = Department::query()->findOrFail($id);
        $department->update(['is_deleted' => false]);

        return back()->with('success', 'Jurusan berhasil direstore.');
    }

    public function forceDeleteDepartment(Request $request, int $id): RedirectResponse
    {
        if (! Schema::hasColumn('departments', 'is_deleted')) {
            return back()->with('error', 'Fitur delete permanent jurusan belum tersedia di database ini.');
        }
        if (! $this->canAccessDeletedTab($request)) {
            return back()->with('error', 'Anda tidak punya akses ke tab Deleted.');
        }

        $department = Department::query()->findOrFail($id);
        if (! (bool) ($department->is_deleted ?? false)) {
            return back()->with('error', 'Jurusan harus ada di tab Deleted untuk dihapus permanen.');
        }
        $department->delete();

        return back()->with('success', 'Jurusan berhasil dihapus permanen.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('school_classes', 'name')],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ]);

        SchoolClass::query()->create([
            'name' => trim((string) $data['name']),
            'department_id' => $data['department_id'] ?? null,
        ]);

        return back()->with('success', 'Kelas berhasil ditambahkan.');
    }

    public function updateClass(Request $request, SchoolClass $class): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('school_classes', 'name')->ignore($class->id)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
        ]);

        $class->update([
            'name' => trim((string) $data['name']),
            'department_id' => $data['department_id'] ?? null,
        ]);

        return back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroyClass(SchoolClass $class): RedirectResponse
    {
        if (Schema::hasColumn('school_classes', 'is_deleted')) {
            $class->update(['is_deleted' => true]);
        } else {
            $class->delete();
        }

        return back()->with('success', 'Kelas berhasil dihapus.');
    }

    public function restoreClass(Request $request, int $id): RedirectResponse
    {
        if (! Schema::hasColumn('school_classes', 'is_deleted')) {
            return back()->with('error', 'Fitur restore kelas belum tersedia di database ini.');
        }
        if (! $this->canAccessDeletedTab($request)) {
            return back()->with('error', 'Anda tidak punya akses ke tab Deleted.');
        }

        $class = SchoolClass::query()->findOrFail($id);
        $class->update(['is_deleted' => false]);

        return back()->with('success', 'Kelas berhasil direstore.');
    }

    public function forceDeleteClass(Request $request, int $id): RedirectResponse
    {
        if (! Schema::hasColumn('school_classes', 'is_deleted')) {
            return back()->with('error', 'Fitur delete permanent kelas belum tersedia di database ini.');
        }
        if (! $this->canAccessDeletedTab($request)) {
            return back()->with('error', 'Anda tidak punya akses ke tab Deleted.');
        }

        $class = SchoolClass::query()->findOrFail($id);
        if (! (bool) ($class->is_deleted ?? false)) {
            return back()->with('error', 'Kelas harus ada di tab Deleted untuk dihapus permanen.');
        }
        $class->delete();

        return back()->with('success', 'Kelas berhasil dihapus permanen.');
    }

    private function canAccessDeletedTab(Request $request): bool
    {
        $role = (string) ($request->user()?->role ?? '');
        return MenuAccess::canAccess($role, 'tab/deleted');
    }
}
