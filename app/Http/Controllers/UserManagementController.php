<?php

namespace App\Http\Controllers;

use App\Models\PklLocation;
use App\Models\User;
use App\Support\UsernameResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $userRole = (string) ($request->user()?->role ?? 'siswa');
        $hasDeletedTabAccess = $userRole === 'superadmin';
        $tab = $request->string('tab', 'active')->toString();
        if (! in_array($tab, ['active', 'deleted'], true)) {
            $tab = 'active';
        }

        $isSuperadmin = $request->user()?->role === 'superadmin';
        $isAdminSekolah = $request->user()?->role === 'admin_sekolah';
        $isKajur = $request->user()?->role === 'kajur';
        $deptName = $request->user()?->department_name;

        if ($tab === 'deleted' && ! $hasDeletedTabAccess) {
            $tab = 'active';
        }

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->integer('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
        $q = trim((string) $request->string('q')->toString());

        $usersQuery = $tab === 'deleted'
            ? User::onlyTrashed()->with('pklLocation:id,name')
            : User::query()->withoutTrashed()->with('pklLocation:id,name');

        if (! $isSuperadmin && ! $isAdminSekolah) {
            if ($isKajur) {
                $usersQuery->whereIn('role', $this->allowedKajurManagedRoles());
                if (filled($deptName)) {
                    $usersQuery->where('department_name', $deptName);
                }
            } else {
                // Pengamanan jika role lain entah bagaimana masuk sini
                $usersQuery->where('id', $request->user()->id);
            }
        }

        if (! $isSuperadmin) {
            $usersQuery->where('role', '!=', 'superadmin');
        }

        $activeCountQuery = User::query()->withoutTrashed();
        $deletedCountQuery = User::onlyTrashed();

        if (! $isSuperadmin && ! $isAdminSekolah && $isKajur) {
            $activeCountQuery->whereIn('role', $this->allowedKajurManagedRoles());
            $deletedCountQuery->whereIn('role', $this->allowedKajurManagedRoles());
            if (filled($deptName)) {
                $activeCountQuery->where('department_name', $deptName);
                $deletedCountQuery->where('department_name', $deptName);
            }
        }

        if (! $isSuperadmin) {
            $activeCountQuery->where('role', '!=', 'superadmin');
            $deletedCountQuery->where('role', '!=', 'superadmin');
        }

        $visibleRoles = $this->visibleRoles($isSuperadmin);
        if ($isKajur) {
            $visibleRoles = $this->allowedKajurManagedRoles();
        }
        $roleFilter = trim((string) $request->string('role')->toString());
        if (! in_array($roleFilter, $visibleRoles, true)) {
            $roleFilter = 'all';
        }

        if ($roleFilter !== 'all') {
            $usersQuery->where('role', $roleFilter);
        }

        if ($q !== '') {
            $usersQuery->where(function ($query) use ($q): void {
                $query->where('name', 'like', '%'.$q.'%')
                    ->orWhere('nis', 'like', '%'.$q.'%')
                    ->orWhere('nuptk', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('class_name', 'like', '%'.$q.'%')
                    ->orWhere('department_name', 'like', '%'.$q.'%');
            });
        }

        $classOptionsQuery = User::query()
            ->withoutTrashed()
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '');

        $departmentOptionsQuery = User::query()
            ->withoutTrashed()
            ->whereNotNull('department_name')
            ->where('department_name', '!=', '');

        if ($isKajur && filled($deptName)) {
            $classOptionsQuery->where('department_name', $deptName);
            $departmentOptionsQuery->where('department_name', $deptName);
        }

        $classOptions = $classOptionsQuery
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name')
            ->values();

        $departmentOptions = $departmentOptionsQuery
            ->distinct()
            ->orderBy('department_name')
            ->pluck('department_name')
            ->values();

        return view('users.index', [
            'title' => 'Manajemen Pengguna',
            'tab' => $tab,
            'isSuperadmin' => $isSuperadmin,
            'isKajur' => $isKajur,
            'hasDeletedTabAccess' => $hasDeletedTabAccess,
            'activeCount' => $activeCountQuery->count(),
            'deletedCount' => $deletedCountQuery->count(),
            'users' => $usersQuery->latest()->paginate($perPage)->withQueryString(),
            'roles' => $visibleRoles,
            'staffRoles' => $this->staffRoles($visibleRoles),
            'pembimbings' => User::query()
                ->where('role', 'pembimbing_pkl')
                ->where('is_deleted', false)
                ->when($isKajur && $deptName, fn ($q) => $q->where('department_name', $deptName))
                ->orderBy('name')
                ->get(['id', 'name']),
            'pklLocations' => PklLocation::query()->orderBy('name')->get(['id', 'name']),
            'perPage' => $perPage,
            'perPageOptions' => $allowedPerPage,
            'filters' => [
                'q' => $q,
                'role' => $roleFilter,
            ],
            'roleLabels' => [
                'instruktur' => 'Instruktur PKL',
                'pembimbing_pkl' => 'Pembimbing',
            ],
            'actorDepartmentName' => $deptName,
            'classOptions' => $classOptions,
            'departmentOptions' => $departmentOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $actor = $request->user();
        $targetRole = $request->input('role');

        if ($actor?->role !== 'superadmin' && $targetRole === 'superadmin') {
            return back()->with('error', 'Hanya superadmin yang boleh membuat role superadmin.');
        }

        if ($actor?->role === 'kajur' && ! in_array((string) $targetRole, $this->allowedKajurManagedRoles(), true)) {
            return back()->with('error', 'Kajur hanya diperbolehkan menambah user dengan role Pembimbing Sekolah atau pembimbing.');
        }

        $base = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nis')],
            'nuptk' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nuptk')],
            'class_name' => ['nullable', 'string', 'max:100'],
            'department_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')],
            'role' => ['required', 'string', Rule::in($this->roles())],
            'pkl_location_id' => ['nullable', 'integer', Rule::exists('pkl_locations', 'id')],
            'pembimbing_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'password' => ['required', 'string', 'min:6'],
            'is_school_mentor_all_students' => ['nullable', 'boolean'],
        ]);

        $data = $this->normalizeIdentityPayload($base);

        if ($actor?->role === 'kajur' && (string) ($data['role'] ?? '') === 'pembimbing_pkl') {
            $this->ensurePembimbingQuotaForKajur($actor, null);
        }
        
        // Pengamanan department_name untuk Kajur
        if ($request->user()?->role === 'kajur' && $request->user()?->department_name) {
            $data['department_name'] = $request->user()->department_name;
        }

        $username = app(UsernameResolver::class)->generateUnique(
            null,
            (string) ($data['nis'] ?? ''),
            (string) ($data['nuptk'] ?? ''),
            (string) ($data['email'] ?? '')
        );

        User::create([
            'name' => $data['name'],
            'username' => $username,
            'nis' => $data['nis'],
            'nuptk' => $data['nuptk'],
            'class_name' => $data['class_name'] ?? null,
            'department_name' => $data['department_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'pkl_location_id' => $data['pkl_location_id'] ?? null,
            'pembimbing_user_id' => $base['pembimbing_user_id'] ?? null,
            'is_school_mentor_all_students' => (bool) ($base['is_school_mentor_all_students'] ?? false),
            'password' => Hash::make($data['password']),
            'must_reset_password' => true,
            'must_change_password' => true,
            'email_verified_at' => now(),
            'phone_verified_at' => filled($data['phone'] ?? null) ? now() : null,
            'is_google_linked' => false,
            'is_otp_active' => false,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
            'is_deleted' => false,
        ]);

        return back()->with('success', 'User berhasil dibuat.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $targetRole = $request->input('role');

        if ($actor?->role !== 'superadmin' && ($user->role === 'superadmin' || $targetRole === 'superadmin')) {
            return back()->with('error', 'Hanya superadmin yang boleh mengubah akun superadmin.');
        }

        if ($actor?->role === 'kajur') {
            if (! in_array((string) $user->role, $this->allowedKajurManagedRoles(), true)) {
                return back()->with('error', 'Kajur hanya diperbolehkan mengelola user dengan role Pembimbing Sekolah atau pembimbing.');
            }
            if (! in_array((string) $targetRole, $this->allowedKajurManagedRoles(), true)) {
                return back()->with('error', 'Role hanya boleh diatur sebagai Pembimbing Sekolah atau pembimbing.');
            }
        }

        $base = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nis' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nis')->ignore($user->id)],
            'nuptk' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nuptk')->ignore($user->id)],
            'class_name' => ['nullable', 'string', 'max:100'],
            'department_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in($this->roles())],
            'pkl_location_id' => ['nullable', 'integer', Rule::exists('pkl_locations', 'id')],
            'pembimbing_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'password' => ['nullable', 'string', 'min:6'],
            'is_school_mentor_all_students' => ['nullable', 'boolean'],
        ]);

        $data = $this->normalizeIdentityPayload($base, true, $user->id);

        if ($actor?->role === 'kajur' && (string) ($data['role'] ?? '') === 'pembimbing_pkl') {
            $this->ensurePembimbingQuotaForKajur($actor, $user->id);
        }

        // Pengamanan department_name untuk Kajur
        if ($request->user()?->role === 'kajur' && $request->user()?->department_name) {
            $data['department_name'] = $request->user()->department_name;
        }

        $username = app(UsernameResolver::class)->generateUnique(
            (string) ($user->username ?? ''),
            (string) ($data['nis'] ?? ''),
            (string) ($data['nuptk'] ?? ''),
            (string) ($data['email'] ?? ''),
            $user->id
        );

        $payload = [
            'name' => $data['name'],
            'username' => $username,
            'nis' => $data['nis'],
            'nuptk' => $data['nuptk'],
            'class_name' => $data['class_name'] ?? null,
            'department_name' => $data['department_name'] ?? null,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'pkl_location_id' => $data['pkl_location_id'] ?? null,
            'pembimbing_user_id' => $base['pembimbing_user_id'] ?? null,
            'is_school_mentor_all_students' => (bool) ($base['is_school_mentor_all_students'] ?? false),
            'updated_by' => $request->user()?->id,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
            $payload['must_reset_password'] = true;
            $payload['must_change_password'] = true;
        }

        if (strtolower(trim((string) $user->email)) !== strtolower(trim((string) $data['email']))) {
            $payload['email_verified_at'] = null;
            $payload['is_otp_active'] = false;
        }
        if (trim((string) $user->phone) !== trim((string) ($data['phone'] ?? ''))) {
            $payload['phone_verified_at'] = null;
            $payload['is_otp_active'] = false;
        }

        $user->update($payload);
        return back()->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        try {
            $user = User::withTrashed()->findOrFail($id);
        } catch (ModelNotFoundException) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        if ($user->trashed()) {
            return back()->with('success', 'User sudah berada di tab Deleted.');
        }

        if ((int) $request->user()?->id === (int) $user->id) {
            return back()->with('error', 'Akun sendiri tidak dapat dihapus.');
        }

        if ($request->user()?->role !== 'superadmin' && $user->role === 'superadmin') {
            return back()->with('error', 'Hanya superadmin yang boleh menghapus akun superadmin.');
        }

        if ($request->user()?->role === 'kajur' && $request->user()?->department_name !== $user->department_name) {
            return back()->with('error', 'Anda hanya dapat menghapus pengguna dari jurusan Anda sendiri.');
        }

        try {
            $user->update([
                'deleted_by' => $request->user()?->id,
                'is_deleted' => true,
            ]);

            $user->delete();

            return back()->with('success', 'User berhasil di delete.');
        } catch (\Throwable $e) {
            Log::error('Gagal soft delete user.', [
                'user_id' => $user->id,
                'actor_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal menghapus user. Silakan coba lagi.');
        }
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $actor = $request->user();
        if (($actor?->role ?? null) !== 'superadmin') {
            return back()->with('error', 'Hanya superadmin yang boleh restore user.');
        }
        $user = User::withTrashed()->findOrFail($id);

        $user->restore();
        $user->update([
            'deleted_by' => null,
            'is_deleted' => false,
        ]);

        return back()->with('success', 'User berhasil direstore.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        if ($request->user()?->role !== 'superadmin') {
            return back()->with('error', 'Hanya superadmin yang boleh hapus permanen.');
        }

        $user = User::withTrashed()->findOrFail($id);

        if (! $user->trashed()) {
            return back()->with('error', 'User harus ada di tab Deleted untuk dihapus permanen.');
        }

        if ((int) $request->user()->id === (int) $user->id) {
            return back()->with('error', 'Akun sendiri tidak dapat dihapus permanen.');
        }

        try {
            $user->forceDelete();

            return back()->with('success', 'User berhasil dihapus permanen.');
        } catch (\Throwable $e) {
            Log::error('Gagal force delete user.', [
                'user_id' => $user->id,
                'actor_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal hapus permanen user. Silakan coba lagi.');
        }
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['delete', 'restore', 'force_delete'])],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer'],
        ]);

        $action = (string) $data['action'];
        $selectedIds = collect($data['selected_ids'])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return back()->with('error', 'Tidak ada user yang dipilih.');
        }

        $actor = $request->user();
        $isPowerUser = in_array($actor?->role, ['superadmin', 'admin_sekolah', 'kajur'], true);

        if (! $isPowerUser) {
            return back()->with('error', 'Anda tidak memiliki hak untuk melakukan aksi massal.');
        }

        if (in_array($action, ['restore', 'force_delete'], true) && $actor?->role !== 'superadmin') {
            return back()->with('error', 'Hanya superadmin yang boleh melakukan aksi restore/hapus permanen.');
        }

        if (in_array($action, ['restore', 'delete'], true) && ! $isPowerUser) {
            return back()->with('error', 'Anda tidak memiliki hak untuk melakukan aksi ini.');
        }

        $users = User::withTrashed()->whereIn('id', $selectedIds)->get();
        $processed = 0;
        $skipped = 0;

        foreach ($users as $user) {
            try {
                // Validasi jurusan untuk Kajur
                if ($actor->role === 'kajur' && $actor->department_name !== $user->department_name) {
                    $skipped++;
                    continue;
                }
                if ($action === 'delete') {
                    if ($user->trashed()) {
                        $skipped++;
                        continue;
                    }

                    if ((int) $request->user()?->id === (int) $user->id) {
                        $skipped++;
                        continue;
                    }

                    if ($request->user()?->role !== 'superadmin' && $user->role === 'superadmin') {
                        $skipped++;
                        continue;
                    }

                    $user->update([
                        'deleted_by' => $request->user()?->id,
                        'is_deleted' => true,
                    ]);
                    $user->delete();
                    $processed++;
                    continue;
                }

                if ($action === 'restore') {
                    if (! $user->trashed()) {
                        $skipped++;
                        continue;
                    }

                    $user->restore();
                    $user->update([
                        'deleted_by' => null,
                        'is_deleted' => false,
                    ]);
                    $processed++;
                    continue;
                }

                if (! $user->trashed()) {
                    $skipped++;
                    continue;
                }

                if ((int) $request->user()?->id === (int) $user->id) {
                    $skipped++;
                    continue;
                }

                $user->forceDelete();
                $processed++;
            } catch (\Throwable $e) {
                $skipped++;
                Log::error('Gagal bulk action user.', [
                    'action' => $action,
                    'target_user_id' => $user->id,
                    'actor_id' => $request->user()?->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $label = match ($action) {
            'delete' => 'Delete',
            'restore' => 'Restore',
            'force_delete' => 'Delete Permanent',
            default => 'Aksi',
        };

        return back()->with('success', "{$label} massal selesai: {$processed} berhasil, {$skipped} dilewati.");
    }

    private function roles(): array
    {
        return [
            'superadmin',
            'admin_sekolah',
            'siswa',
            'pembimbing_pkl',
            'instruktur',
            'kajur',
            'wali_kelas',
            'kesiswaan',
            'kepsek',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function allowedKajurManagedRoles(): array
    {
        return ['pembimbing_pkl', 'instruktur'];
    }

    private function ensurePembimbingQuotaForKajur(?User $actor, ?int $ignoreUserId): void
    {
        $departmentName = trim((string) ($actor?->department_name ?? ''));
        if ($departmentName === '') {
            throw ValidationException::withMessages([
                'department_name' => 'Jurusan kajur belum diatur. Tidak dapat menambah pembimbing sekolah.',
            ]);
        }

        $query = User::query()
            ->withoutTrashed()
            ->where('role', 'pembimbing_pkl')
            ->where('department_name', $departmentName);

        if ($ignoreUserId !== null) {
            $query->where('id', '!=', $ignoreUserId);
        }

        if ($query->count() >= 2) {
            throw ValidationException::withMessages([
                'role' => 'Maksimal 2 pembimbing sekolah aktif per jurusan.',
            ]);
        }
    }

    /**
     * @param array<int, string>|null $roles
     * @return array<int, string>
     */
    private function staffRoles(?array $roles = null): array
    {
        $source = $roles ?? $this->roles();

        return array_values(array_filter(
            $source,
            fn (string $role) => $role !== 'siswa'
        ));
    }

    /**
     * @return array<int, string>
     */
    private function visibleRoles(bool $isSuperadmin): array
    {
        $roles = $this->roles();
        if ($isSuperadmin) {
            return $roles;
        }

        return array_values(array_filter(
            $roles,
            fn (string $role) => $role !== 'superadmin'
        ));
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function normalizeIdentityPayload(array $data, bool $isUpdate = false, ?int $ignoreUserId = null): array
    {
        $role = (string) ($data['role'] ?? '');
        $nis = trim((string) ($data['nis'] ?? ''));
        $nuptk = trim((string) ($data['nuptk'] ?? ''));

        if ($role === 'siswa') {
            if ($nis === '') {
                throw ValidationException::withMessages([
                    'nis' => 'NIS wajib diisi untuk role siswa.',
                ]);
            }
            $data['nis'] = $nis;
            $data['nuptk'] = null;
            $data['pkl_location_id'] = null;

            return $data;
        }

        if ($nuptk === '') {
            $data['nuptk'] = null;
            $data['nis'] = $nis !== '' ? $nis : null;
        } else {
            $data['nuptk'] = $nuptk;
            $data['nis'] = $nis !== '' ? $nis : $nuptk;
        }

        $data['pkl_location_id'] = null;

        if (! $isUpdate && $data['nis'] === null && empty($data['email'])) {
            throw ValidationException::withMessages([
                'email' => 'Email wajib diisi jika NUPTK kosong.',
            ]);
        }

        if ($data['nis'] !== null) {
            $existsNis = User::withTrashed()
                ->where('nis', $data['nis'])
                ->when($ignoreUserId !== null, fn ($query) => $query->where('id', '!=', $ignoreUserId))
                ->exists();

            if ($existsNis) {
                throw ValidationException::withMessages([
                    'nis' => 'NIS/NUPTK ini sudah digunakan.',
                ]);
            }
        }

        return $data;
    }
}

